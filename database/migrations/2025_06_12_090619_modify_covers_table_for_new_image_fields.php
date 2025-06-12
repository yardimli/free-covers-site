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
			Schema::table('covers', function (Blueprint $table) {
				$table->boolean('has_real_2d')->default(true)->after('mockup_2d_path');
				$table->boolean('has_real_3d')->default(true)->after('mockup_3d_path');
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::table('covers', function (Blueprint $table) {
				if (Schema::hasColumn('covers', 'has_real_2d')) {
					$table->dropColumn('has_real_2d');
				}
				if (Schema::hasColumn('covers', 'has_real_3d')) {
					$table->dropColumn('has_real_3d');
				}
			});
		}
	};
