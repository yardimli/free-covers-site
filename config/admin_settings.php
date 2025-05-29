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
			// Covers
			'covers_main' => [
				'originals' => 'covers/main/originals',
				'thumbnails' => 'covers/main/thumbnails',
				'thumb_w' => 200,
				'thumb_h' => 300,
				'thumb_quality' => 85
			],
			'covers_mockup_2d' => [
				'originals' => 'covers/mockups/2d'
			],
			'covers_mockup_3d' => [
				'originals' => 'covers/mockups/3d'
			],
			'covers_full_cover' => [
				'originals' => 'covers/full_cover/originals',
				'thumbnails' => 'covers/full_cover/thumbnails',
				'thumb_w' => 200,
				'thumb_h' => 150
			],

			// Templates
			'templates_cover_image' => [
				'originals' => 'templates/cover_images'
			],
			'templates_full_cover_image' => [
				'originals' => 'templates/full_cover_images/originals',
				'thumbnails' => 'templates/full_cover_images/thumbnails',
				'thumb_w' => 200,
				'thumb_h' => 150
			],

			'elements_main' => [
				'originals' => 'elements/originals',
				'thumbnails' => 'elements/thumbnails',
				'thumb_w' => 100,
				'thumb_h' => 100
			],
			'overlays_main' => [
				'originals' => 'overlays/originals',
				'thumbnails' => 'overlays/thumbnails',
				'thumb_w' => 100,
				'thumb_h' => 100
			],

			'blog_post_images' => [
				'originals' => 'blog/posts/originals',
				'thumbnails' => 'blog/posts/thumbnails',
				'thumb_w' => 300, // Example: Adjust as needed
				'thumb_h' => 200, // Example: Adjust as needed
				'thumb_quality' => 80,
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
