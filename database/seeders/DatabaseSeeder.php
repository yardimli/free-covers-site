<?php

	namespace Database\Seeders;

	use Illuminate\Database\Seeder;

	class DatabaseSeeder extends Seeder
	{
		/**
		 * Seed the application's database.
		 */
		public function run(): void
		{
			// \App\Models\User::factory(10)->create();

			// \App\Models\User::factory()->create([
			//     'name' => 'Test User',
			//     'email' => 'test@example.com',
			// ]);

			// Add other seeders if you have them
			// $this->call(SomeOtherSeeder::class);
			$this->call(CoverTemplateSeeder::class); // Add this line
		}
	}
