<?php

	namespace App\Http\Controllers;

	use App\Models\Cover;
	use App\Models\Template; // Ensure this is present
	use Illuminate\Http\Request; // Make sure this is present
	use Illuminate\Support\Str;
	use Illuminate\Support\Facades\Log; // Import Log facade
	use App\Models\ContactMessage; // Added for contact form
	use Illuminate\Support\Facades\Validator;
	use Illuminate\View\View;

	// Added for validation

	class HomeController extends Controller
	{
		public function index()
		{
			// Fetch all covers to determine categories and their counts
			$allCoversForCategories = Cover::select(['id', 'categories'])->where('cover_type_id', 1)->get();
			$genreCounts = [];
			foreach ($allCoversForCategories as $cover) {
				if (is_array($cover->categories)) {
					foreach ($cover->categories as $category) {
						$categoryName = Str::title(trim($category)); // Normalize to TitleCase
						if (!empty($categoryName)) {
							$genreCounts[$categoryName] = ($genreCounts[$categoryName] ?? 0) + 1;
						}
					}
				}
			}
			$filteredGenreCounts = array_filter($genreCounts, function ($count) {
				return $count >= 4;
			});
			//sort by count descending
			arsort($filteredGenreCounts);

			$coversForFirstTab = [];
			$firstGenreName = null; // This will be TitleCase
			if (!empty($filteredGenreCounts)) {
				$firstGenreName = array_key_first($filteredGenreCounts);
				if ($firstGenreName) {
					$dbQueryGenreNameLower = Str::lower($firstGenreName); // e.g., "fiction"
					// $firstGenreName itself is TitleCase, e.g., "Fiction"
					$coversForFirstTab[$firstGenreName] = Cover::with('templates') // Eager load templates
					->where(function ($query) use ($dbQueryGenreNameLower, $firstGenreName) {
						$query->whereJsonContains('categories', $dbQueryGenreNameLower); // Check for "fiction"
						if ($dbQueryGenreNameLower !== $firstGenreName) {
							$query->orWhereJsonContains('categories', $firstGenreName); // Check for "Fiction"
						}
					})
						->where('cover_type_id', 1) // Added this condition
						->inRandomOrder()
						->take(6) // <--- CHANGED FROM 18 to 6
						->get();

					foreach ($coversForFirstTab[$firstGenreName] as &$cover) {
						// Add random template overlay URL
						$cover['random_template_overlay_url'] = null;
						$cover['random_template_overlay_id'] = null; // Add this line to store the ID
						if ($cover->templates->isNotEmpty()) {
							$randomTemplate = $cover->templates->random();
							if ($randomTemplate->cover_image_path) {
								$cover['random_template_overlay_url'] = asset('storage/' . $randomTemplate->cover_image_path);
								$cover['random_template_overlay_id'] = $randomTemplate->id; // Store the ID
							}
						}
					}
					unset($cover);
				}
			}

			$newArrivals = Cover::with('templates') // Eager load templates
			->orderBy('created_at', 'desc')
				->where('cover_type_id', 1)
				->inRandomOrder()
				->take(6)
				->get();

			foreach ($newArrivals as &$cover) {
				// Add random template overlay URL
				$cover['random_template_overlay_url'] = null;
				$cover['random_template_overlay_id'] = null; // Add this line to store the ID
				if ($cover->templates->isNotEmpty()) {
					$randomTemplate = $cover->templates->random();
					if ($randomTemplate->cover_image_path) {
						$cover['random_template_overlay_url'] = asset('storage/' . $randomTemplate->cover_image_path);
						$cover['random_template_overlay_id'] = $randomTemplate->id; // Store the ID
					}
				}
			}
			unset($cover);

			return view('index', [
				'genreCounts' => $filteredGenreCounts,
				'coversForTabs' => $coversForFirstTab,
				'newArrivals' => $newArrivals,
			]);
		}

		/**
		 * Fetch covers for a specific genre via AJAX.
		 */
		public function getCoversForGenre(Request $request, $genreSlug)
		{
			$genreDisplayName = $request->query('name'); // This is TitleCase from the JS (e.g., "Fiction")
			if (empty($genreDisplayName)) {
				Log::warning("Required 'name' query parameter missing or empty for genre slug: " . $genreSlug . ". AJAX call might be misconfigured from JS.");
				return response()->json(['covers' => [], 'message' => 'Genre specification is incomplete. The "name" query parameter is missing.'], 400);
			}
			$dbQueryGenreNameLower = Str::lower($genreDisplayName); // e.g., "fiction"

			$covers = Cover::with('templates') // Eager load templates
			->where(function ($query) use ($dbQueryGenreNameLower, $genreDisplayName) { // Group OR conditions
				$query->whereJsonContains('categories', $dbQueryGenreNameLower) // Check for "fiction"
				->orWhereJsonContains('categories', $genreDisplayName); // Check for "Fiction"
			})
				->where('cover_type_id', 1)
				->inRandomOrder()
				->take(6) // <--- CHANGED FROM 18 to 6
				->get();

			$formattedCovers = $covers->map(function ($cover) {
				$randomTemplateOverlayUrl = null;
				$randomTemplateOverlayId = null; // Initialize the ID
				if ($cover->templates->isNotEmpty()) {
					$randomTemplate = $cover->templates->random();
					if ($randomTemplate->cover_image_path) {
						$randomTemplateOverlayUrl = asset('storage/' . $randomTemplate->cover_image_path);
						$randomTemplateOverlayId = $randomTemplate->id; // Store the ID
					}
				}
				return [
					'id' => $cover->id,
					'name' => $cover->name,
					'mockup_2d_path' => $cover->mockup_2d_path ? asset('storage/' . $cover->mockup_2d_path) : null,
					'random_template_overlay_url' => $randomTemplateOverlayUrl,
					'random_template_overlay_id' => $randomTemplateOverlayId, // Include the ID
					'show_url' => route('covers.show', $cover->id),
					'limited_name' => Str::limit($cover->caption, 40),
					'caption' => $cover->caption,
				];
			});
			return response()->json(['covers' => $formattedCovers]);
		}

		/**
		 * Display the About Us page.
		 *
		 * @return \Illuminate\View\View
		 */
		public function about()
		{
			return view('about');
		}

		/**
		 * Display the Contact Us page.
		 *
		 * @return \Illuminate\View\View
		 */
		public function showContactForm()
		{
			return view('contact');
		}

		/**
		 * Handle the submission of the Contact Us form.
		 *
		 * @param  \Illuminate\Http\Request  $request
		 * @return \Illuminate\Http\RedirectResponse
		 */
		public function submitContactForm(Request $request)
		{
			$validator = Validator::make($request->all(), [
				'name' => 'required|string|max:255',
				'email' => 'required|email|max:255',
				'message' => 'required|string|min:10',
			]);

			if ($validator->fails()) {
				return redirect()->route('contact.show')
					->withErrors($validator)
					->withInput();
			}

			ContactMessage::create([
				'name' => $request->input('name'),
				'email' => $request->input('email'),
				'message' => $request->input('message'),
			]);

			return redirect()->route('contact.show')->with('success', 'Thank you for your message! We will get back to you soon.');
		}

		public function faq(): View
		{
			return view('faq');
		}
	}
