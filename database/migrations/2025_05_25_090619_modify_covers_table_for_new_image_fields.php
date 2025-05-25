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
			// First, rename the columns
			Schema::table('covers', function (Blueprint $table) {
				if (Schema::hasColumn('covers', 'image_path')) {
					$table->renameColumn('image_path', 'cover_path');
				}
				if (Schema::hasColumn('covers', 'thumbnail_path')) {
					$table->renameColumn('thumbnail_path', 'cover_thumbnail_path');
				}
			});

			// Then, add new columns in a separate schema call
			Schema::table('covers', function (Blueprint $table) {
				$table->string('mockup_2d_path')->nullable()->after('cover_thumbnail_path');
				$table->string('mockup_3d_path')->nullable()->after('mockup_2d_path');
				$table->string('full_cover_path')->nullable()->after('mockup_3d_path');
				$table->string('full_cover_thumbnail_path')->nullable()->after('full_cover_path');
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			// Drop new columns
			Schema::table('covers', function (Blueprint $table) {
				if (Schema::hasColumn('covers', 'full_cover_thumbnail_path')) {
					$table->dropColumn('full_cover_thumbnail_path');
				}
				if (Schema::hasColumn('covers', 'full_cover_path')) {
					$table->dropColumn('full_cover_path');
				}
				if (Schema::hasColumn('covers', 'mockup_3d_path')) {
					$table->dropColumn('mockup_3d_path');
				}
				if (Schema::hasColumn('covers', 'mockup_2d_path')) {
					$table->dropColumn('mockup_2d_path');
				}
			});

			// Rename columns back in a separate schema call
			Schema::table('covers', function (Blueprint $table) {
				if (Schema::hasColumn('covers', 'cover_thumbnail_path')) {
					$table->renameColumn('cover_thumbnail_path', 'thumbnail_path');
				}
				if (Schema::hasColumn('covers', 'cover_path')) {
					$table->renameColumn('cover_path', 'image_path');
				}
			});
		}
	};
