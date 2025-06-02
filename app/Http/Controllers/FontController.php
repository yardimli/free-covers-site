<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Http;
	use Illuminate\Support\Facades\Log;

	class FontController extends Controller
	{
		public function serveGoogleFontsCss(Request $request)
		{
			// The 'family' parameter from JS will be a string like:
			// "family=Open+Sans:ital,wght@0,300;0,400&family=Roboto:ital,wght@0,500"
			$rawFontFamiliesParam = $request->getQueryString(); // Get the raw query string part

			if (empty($rawFontFamiliesParam)) {
				return response("Missing font family query parameters.", 400)->header('Content-Type', 'text/plain');
			}

			// The raw query string already contains "family=Font1:spec&family=Font2:spec"
			// We just need to add display=swap if it's not already there (though JS should add it)
			// However, Google's css2 endpoint expects 'family' params and 'display' separately.

			// Let's parse the incoming query string to extract all 'family' parameters
			parse_str($rawFontFamiliesParam, $queryParams);

			$fontFamilies = [];
			if (isset($queryParams['family'])) {
				// If 'family' is a single string, make it an array
				// If it's already an array (multiple family params), it's fine
				$fontFamilies = is_array($queryParams['family']) ? $queryParams['family'] : [$queryParams['family']];
			}

			if (empty($fontFamilies)) {
				Log::warning("FontController: No 'family' parameters found after parsing query string: " . $rawFontFamiliesParam);
				return response("No valid 'family' parameters provided.", 400)->header('Content-Type', 'text/plain');
			}

			// Construct the query data for Google Fonts API
			$googleApiQueryData = [
				'family' => $fontFamilies, // This will be correctly encoded by Http client as repeated family params
				'display' => 'swap'
			];

			$googleFontBaseUrl = "https://fonts.googleapis.com/css2";

			try {
				$userAgent = $request->header('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

				Log::info("FontController: Fetching Google Fonts. Base URL: {$googleFontBaseUrl}, Query Data: ", $googleApiQueryData);

				$response = Http::withHeaders([
					'User-Agent' => $userAgent
				])->get($googleFontBaseUrl, $googleApiQueryData); // Pass query data as an array

				if ($response->successful()) {
					$cssContent = $response->body();
					return response($cssContent)->header('Content-Type', 'text/css');
				} else {
					Log::error("FontController: Failed to fetch Google Fonts CSS. Status: " . $response->status() . " URL: " . $response->effectiveUri() . " Response Body: " . $response->body());
					return response("Error fetching font CSS from Google. Status: " . $response->status(), $response->status())
						->header('Content-Type', 'text/plain');
				}
			} catch (\Exception $e) {
				Log::error("FontController: Exception fetching Google Fonts CSS: " . $e->getMessage() . " Query Data: ", $googleApiQueryData);
				return response("Server error while fetching font CSS.", 500)->header('Content-Type', 'text/plain');
			}
		}
	}
