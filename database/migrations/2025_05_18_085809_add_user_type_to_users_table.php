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
			Schema::table('users', function (Blueprint $table) {
				// Add user_type column after 'email' or any other suitable column
				// Default to 1 (regular user), 2 will be for admin
				$table->tinyInteger('user_type')->default(1)->after('email_verified_at')->comment('1: User, 2: Admin');
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::table('users', function (Blueprint $table) {
				$table->dropColumn('user_type');
			});
		}
	};
