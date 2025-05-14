<?php

	namespace Database\Seeders;

	use Illuminate\Database\Seeder;
	use App\Models\Cover;
	use App\Models\Template;
	use Illuminate\Support\Facades\DB;

	class CoverTemplateSeeder extends Seeder
	{
		/**
		 * Run the database seeds.
		 */
		public function run(): void
		{
			$covers = Cover::with('coverType')->get(); // Eager load coverType to get cover_type_id

			if ($covers->isEmpty()) {
				$this->command->info('No covers found to seed template assignments.');
				return;
			}

			$this->command->info("Found {$covers->count()} covers to process for template assignment.");

			DB::transaction(function () use ($covers) {
				foreach ($covers as $cover) {
					if (!$cover->cover_type_id) {
						$this->command->warn("Cover ID {$cover->id} ('{$cover->name}') has no cover_type_id, skipping template assignment.");
						continue;
					}

					// Find templates with the same cover_type_id
					$assignableTemplates = Template::where('cover_type_id', $cover->cover_type_id)->get();

					if ($assignableTemplates->isEmpty()) {
						$this->command->info("No templates found for cover type ID {$cover->cover_type_id} (Cover '{$cover->name}').");
						continue;
					}

					// Select up to 2 random templates
					$templatesToAssign = $assignableTemplates->random(min(2, $assignableTemplates->count()))->pluck('id');

					if ($templatesToAssign->isNotEmpty()) {
						// Use syncWithoutDetaching to avoid errors if seeder is run multiple times
						// or if you want to add to existing relations without removing others.
						// For a fresh seed, sync() is also fine.
						$cover->templates()->syncWithoutDetaching($templatesToAssign->all());
						$this->command->info("Assigned " . $templatesToAssign->count() . " templates to Cover ID {$cover->id} ('{$cover->name}').");
					} else {
						$this->command->info("No suitable templates to assign for Cover ID {$cover->id} ('{$cover->name}').");
					}
				}
			});
			$this->command->info('Cover-Template seeding completed.');
		}
	}
