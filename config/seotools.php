<?php
/**
 * @see https://github.com/artesaos/seotools
 */

// config/seotools.php

	return [
		'meta' => [
			/*
			 * The default configurations to be used by the meta generator.
			 */
			'defaults'       => [
				'title'        => 'Free Kindle Covers', // The default title
				'titleBefore'  => false, // Put defaults.title before page title, like 'Free Kindle Covers - My Page'
				'description'  => 'Discover and customize professionally designed, high-quality book cover templates for your eBooks and print books, completely free.', // Default description
				'separator'    => ' - ',
				'keywords'     => ['free book covers', 'kindle covers', 'ebook covers', 'cover designer', 'premade book covers'],
				'canonical'    => null, // Set null for using 'path' as canonical URL
				'robots'       => 'all', // Or 'index, follow'
			],
			// ...
		],
		'opengraph' => [
			/*
			 * The default configurations to be used by the opengraph generator.
			 */
			'defaults' => [
				'title'       => 'Free Kindle Covers - Your Next Premium Book For Free', // OG Title
				'description' => 'Discover and customize professionally designed, high-quality book cover templates for your eBooks and print books, completely free.', // OG Description
				'url'         => null, // Set null for current URL
				'type'        => 'website',
				'site_name'   => 'Free Kindle Covers',
				'images'      => [asset('images/og-image.png')], // IMPORTANT: Create a default OpenGraph image and place it in public/images/og-image.png
			],
		],
		'twitter' => [
			/*
			 * The default values to be used by the twitter cards generator.
			 */
			'defaults' => [
				//'card'        => 'summary',
				//'site'        => '@YourTwitterHandle', // Optional: Your Twitter handle
			],
		],
		'json-ld' => [
			/*
			 * The default configurations to be used by the json-ld generator.
			 */
			'defaults' => [
				'title'       => 'Free Kindle Covers - Your Next Premium Book For Free', // JSON-LD Title
				'description' => 'Discover and customize professionally designed, high-quality book cover templates for your eBooks and print books, completely free.', // JSON-LD Description
				'url'         => null, // Set null for current URL
				'type'        => 'WebSite',
				'images'      => [asset('images/og-image.png')], // Use the same default image
			],
		],
	];
