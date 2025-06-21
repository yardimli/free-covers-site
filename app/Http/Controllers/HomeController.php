<?php namespace App\Http\Controllers;

use App\Models\Cover;
use App\Models\Template; // Ensure this is present
use Illuminate\Http\Request; // Make sure this is present
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log; // Import Log facade
use App\Models\ContactMessage; // Added for contact form
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Artesaos\SEOTools\Facades\SEOTools; // Added for validation

class HomeController extends Controller
{
	public function index()
	{
		SEOTools::setTitle('Home');
		SEOTools::setDescription('Discover and customize professionally designed, high-quality book cover templates for your eBooks and print books, completely free.');
		SEOTools::opengraph()->setUrl(route('home'));
		SEOTools::setCanonical(route('home'));
		// SEOTools::metatags()->addKeyword(['your', 'main', 'keywords']);

		// Fetch all unique categories for the tag cloud
		$allCoversForCategories = Cover::select(['categories'])
			->where('cover_type_id', 1) // Assuming Kindle covers
			->whereNotNull('categories')
			->whereRaw("JSON_LENGTH(categories) > 0")
			->get();

		$categoryCounts = [];
		foreach ($allCoversForCategories as $cover) {
			if (is_array($cover->categories)) {
				foreach ($cover->categories as $category) {
					$categoryName = Str::title(trim($category)); // Normalize to TitleCase for keys
					if (!empty($categoryName)) {
						$categoryCounts[$categoryName] = ($categoryCounts[$categoryName] ?? 0) + 1;
					}
				}
			}
		}
		// Filter categories that have at least 20 covers
		$availableCategories = array_filter($categoryCounts, function ($count) {
			return $count >= 20;
		});
		ksort($availableCategories); // Sort category names alphabetically

		// Fetch a random selection of covers from the newest 200
		$latestCoverIds = Cover::where('cover_type_id', 1)
			->latest()
			->take(200)
			->pluck('id');

		$covers = Cover::with('templates')
			->whereIn('id', $latestCoverIds)
			->inRandomOrder()
			->take(24) // Display 24 random covers from the pool
			->get();

		// Prepare covers with mockup and random template overlay
		foreach ($covers as $cover) {
			$cover->random_template_overlay_url = null;
			$cover->random_template_overlay_id = null;
			if ($cover->templates->isNotEmpty()) {
				$randomTemplate = $cover->templates->random();
				if ($randomTemplate->cover_image_path) {
					$cover->random_template_overlay_url = asset('storage/' . $randomTemplate->cover_image_path);
					$cover->random_template_overlay_id = $randomTemplate->id;
				}
			}
		}

		return view('index', compact('covers', 'availableCategories'));
	}

	/**
	 * Display the About Us page.
	 *
	 * @return \Illuminate\View\View
	 */
	public function about()
	{
		SEOTools::setTitle('About Us');
		SEOTools::setDescription('Learn about the mission of Free Kindle Covers and our dedication to providing high-quality, free resources for authors.');
		SEOTools::opengraph()->setUrl(route('about'));
		SEOTools::setCanonical(route('about'));
		return view('about');
	}

	/**
	 * Display the Contact Us page.
	 *
	 * @return \Illuminate\View\View
	 */
	public function showContactForm()
	{
		SEOTools::setTitle('Contact Us');
		SEOTools::setDescription('Have a question or feedback? Get in touch with the Free Kindle Covers team. We would love to hear from you.');
		SEOTools::opengraph()->setUrl(route('contact.show'));
		SEOTools::setCanonical(route('contact.show'));
		return view('contact');
	}

	/**
	 * Handle the submission of the Contact Us form.
	 *
	 * @param \Illuminate\Http\Request $request
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
		SEOTools::setTitle('Frequently Asked Questions (FAQ)');
		SEOTools::setDescription('Find answers to common questions about using our cover designer, licensing, and customizing Kindle and print covers.');
		SEOTools::opengraph()->setUrl(route('faq'));
		SEOTools::setCanonical(route('faq'));
		return view('faq');
	}
}
