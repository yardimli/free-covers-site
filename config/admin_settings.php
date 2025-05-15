<?php

	return [
		/*
		|--------------------------------------------------------------------------
		| Admin Panel Settings
		|--------------------------------------------------------------------------
		*/

		// Base path for uploads within the Laravel public storage disk
		// Files will be stored in storage/app/public/admin_uploads
		// Accessible via /storage/admin_uploads after running `php artisan storage:link`
		'upload_path_prefix' => 'admin_uploads',

		'items_per_page' => 30,
		'thumbnail_quality' => 85, // For JPEGs

		'paths' => [
			'covers' => [
				'originals' => 'covers/originals', // Relative to upload_path_prefix
				'thumbnails' => 'covers/thumbnails',
				'thumb_w' => 150,
				'thumb_h' => 236,
			],
			'elements' => [
				'originals' => 'elements/originals',
				'thumbnails' => 'elements/thumbnails',
				'thumb_w' => 150,
				'thumb_h' => 150,
			],
			'overlays' => [
				'originals' => 'overlays/originals',
				'thumbnails' => 'overlays/thumbnails',
				'thumb_w' => 150,
				'thumb_h' => 150,
			],
			'templates' => [
				// JSON content is stored in DB, thumbnails are stored
				'thumbnails' => 'templates/thumbnails',
				'thumb_w' => 150,
				'thumb_h' => 236,
			],
		],

		// For AI generated template files (if you decide to save them to disk again)
		// Relative to upload_path_prefix
		'ai_generated_templates_dir' => 'text-templates-ai',

		// OpenAI Models
		'openai_vision_model' => env('OPENAI_VISION_MODEL', 'gpt-4.1-mini-2025-04-14'), // For image analysis
		'openai_text_model' => env('OPENAI_TEXT_MODEL', 'gpt-4.1-mini-2025-04-14'), // For text generation
		'openai_api_key' => env('OPENAI_API_KEY'),
	];
