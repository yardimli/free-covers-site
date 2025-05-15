<?php

	namespace App\Services;

	use Illuminate\Support\Facades\Http;
	use Illuminate\Support\Facades\Log;

	class OpenAiService
	{
		protected string $apiKey;
		protected string $visionModel;
		protected string $textModel;

		public function __construct()
		{
			$this->apiKey = config('admin_settings.openai_api_key');
			$this->visionModel = config('admin_settings.openai_vision_model');
			$this->textModel = config('admin_settings.openai_text_model');

			if (empty($this->apiKey)) {
				Log::error('OpenAI API Key is not configured.');
				// Consider throwing an exception if critical
			}
		}

		// Helper method to sanitize content arrays
		private function sanitizeContentArray($message) {
			$sanitizedContent = [];

			foreach ($message['content'] as $item) {
				$itemCopy = $item;

				// If this is an image_url item
				if (isset($item['type']) && $item['type'] === 'image_url' && isset($item['image_url']['url'])) {
					// Replace base64 image data
					if (strpos($item['image_url']['url'], 'data:image') === 0) {
						$itemCopy['image_url']['url'] = '[BASE64_IMAGE]';
					}
				}

				$sanitizedContent[] = $itemCopy;
			}

			$message['content'] = $sanitizedContent;
			return $message;
		}

		private function callOpenAI(array $messages, string $model, float $temperature = 0.5, int $maxTokens = 1000, ?array $responseFormat = null)
		{
			if (empty($this->apiKey)) {
				return ["error" => "Error: API Key missing."];
			}

			$payload = [
				'model' => $model,
				'messages' => $messages,
				'temperature' => $temperature,
				'max_tokens' => $maxTokens,
				'top_p' => 1,
				'frequency_penalty' => 0,
				'presence_penalty' => 0,
				'n' => 1,
				'stream' => false,
			];

			if ($responseFormat) {
				$payload['response_format'] = $responseFormat;
			}


			// Create a copy of messages for logging, sanitizing image data
			$messagesCopy = array_map(function ($message) {
				if (isset($message['content'])) {
					// Handle array content (like in vision API)
					if (is_array($message['content'])) {
						return $this->sanitizeContentArray($message);
					}
				}
				return $message;
			}, $messages);

			Log::debug("OpenAI Request to model {$model}: ", $messagesCopy);

			$response = Http::withToken($this->apiKey)
				->timeout(180) // seconds
				->post('https://api.openai.com/v1/chat/completions', $payload);

			if ($response->failed()) {
				Log::error("OpenAI API Error: HTTP Code " . $response->status(), [
					'response_body' => $response->body()
				]);
				return ["error" => "API request failed (HTTP " . $response->status() . ": " . ($response->json('error.message') ?? $response->body()) . ")"];
			}

			$responseData = $response->json();
			Log::debug("OpenAI Response: ", $responseData);

			if (isset($responseData['choices'][0]['message']['content'])) {
				return ["content" => trim($responseData['choices'][0]['message']['content'])];
			} elseif (isset($responseData['error'])) {
				Log::error("OpenAI API Error Structure Received: ", $responseData['error']);
				return ["error" => "Error: " . ($responseData['error']['message'] ?? 'Unknown API error structure.')];
			} else {
				Log::error("Unexpected OpenAI API response structure: ", $responseData);
				return ["error" => "Error: Unexpected API response structure."];
			}
		}

		public function generateMetadataFromImageBase64(string $prompt, string $base64Image, string $mimeType = 'image/jpeg'): array
		{
			$dataUri = "data:" . $mimeType . ";base64," . $base64Image;
			$requestMessages = [
				[
					"role" => "user",
					"content" => [
						["type" => "text", "text" => $prompt],
						["type" => "image_url", "image_url" => ["url" => $dataUri, "detail" => "auto"]]
					]
				]
			];
			return $this->callOpenAI($requestMessages, $this->visionModel, 0.5, 300);
		}

		public function generateText(array $messages, float $temperature = 0.6, int $maxTokens = 4000, ?array $responseFormat = null): array
		{
			return $this->callOpenAI($messages, $this->textModel, $temperature, $maxTokens, $responseFormat);
		}

		public function parseAiListResponse(?string $rawResponseContent): array
		{
			if (empty($rawResponseContent)) return [];
			$cleaned = preg_replace('/(^[\s*-]+)/m', '', $rawResponseContent);
			$cleaned = trim($cleaned, " \n\r\t\v\0.,\"'");
			if (empty($cleaned)) return [];
			$items = array_map('trim', explode(',', $cleaned));
			$items = array_filter($items, fn($value) => !empty($value));
			$items = array_unique($items);
			return array_values($items);
		}
	}
