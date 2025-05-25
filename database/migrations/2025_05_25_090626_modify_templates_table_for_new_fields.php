<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		/**
		 * Run the migrations.
		 */
		public function up(): void
		{
			Schema::table('templates', function (Blueprint $table) {
				// Rename existing thumbnail_path
				if (Schema::hasColumn('templates', 'thumbnail_path')) {
					$table->renameColumn('thumbnail_path', 'cover_image_path');
				}
			});

			Schema::table('templates', function (Blueprint $table) {
				if (Schema::hasColumn('templates', 'json_path')) {
					$table->dropColumn('json_path');
				}

				$table->string('full_cover_image_path')->nullable()->after('cover_image_path');
				$table->string('full_cover_image_thumbnail_path')->nullable()->after('full_cover_image_path');

				$table->json('full_cover_json_content')->nullable()->after('json_content');
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::table('templates', function (Blueprint $table) {
				// Drop new columns
				if (Schema::hasColumn('templates', 'full_cover_json_content')) {
					$table->dropColumn('full_cover_json_content');
				}
				if (Schema::hasColumn('templates', 'full_cover_image_thumbnail_path')) {
					$table->dropColumn('full_cover_image_thumbnail_path');
				}
				if (Schema::hasColumn('templates', 'full_cover_image_path')) {
					$table->dropColumn('full_cover_image_path');
				}

				// Add json_path back (as nullable string, adjust if it was different)
				// This assumes you want to revert to its previous state.
				// If it was dropped intentionally, you might not add it back.
				if (!Schema::hasColumn('templates', 'json_path')) {
					$table->string('json_path')->nullable()->after('cover_image_path'); // Or its original position
				}

				// Rename cover_image_path back to thumbnail_path
				if (Schema::hasColumn('templates', 'cover_image_path')) {
					$table->renameColumn('cover_image_path', 'thumbnail_path');
				}

				// If you changed json_content type, revert it here if necessary.
				// Example: $table->text('json_content')->nullable()->change();
			});
		}
	};
