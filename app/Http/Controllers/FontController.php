<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Http;
	use Illuminate\Support\Facades\Log;

	class FontController extends Controller
	{
		public function serveGoogleFontsCss(Request $request)
		{
			// The 'family' parameter will be a string like:
			// "Open+Sans:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Roboto:ital,wght@..."
			$fontFamiliesParam = $request->query('family');

			if (empty($fontFamiliesParam)) {
				return response("Missing 'family' query parameter.", 400)->header('Content-Type', 'text/plain');
			}

			// Construct the full Google Fonts API URL
			// The $fontFamiliesParam already contains the "family=Name:spec&family=Name2:spec" part
			$googleFontUrl = "https://fonts.googleapis.com/css2?{$fontFamiliesParam}&display=swap";

			try {
				// Forward the client's User-Agent, as Google Fonts might serve different CSS/font formats
				$userAgent = $request->header('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

				$response = Http::withHeaders([
					'User-Agent' => $userAgent
				])->get($googleFontUrl);

				if ($response->successful()) {
					$cssContent = $response->body();
					// You could add caching here if desired
					// Cache::put('google_font_css_' . md5($fontFamiliesParam), $cssContent, now()->addHours(24));
					return response($cssContent)->header('Content-Type', 'text/css');
				} else {
					Log::error("Failed to fetch Google Fonts CSS. Status: " . $response->status() . " URL: " . $googleFontUrl . " Params: " . $fontFamiliesParam);
					return response("Error fetching font CSS from Google. Status: " . $response->status(), $response->status()) // Propagate Google's error status
					->header('Content-Type', 'text/plain');
				}
			} catch (\Exception $e) {
				Log::error("Exception fetching Google Fonts CSS: " . $e->getMessage() . " URL: " . $googleFontUrl . " Params: " . $fontFamiliesParam);
				return response("Server error while fetching font CSS.", 500)->header('Content-Type', 'text/plain');
			}
		}
	}
