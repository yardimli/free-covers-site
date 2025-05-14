<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		/**
		 * Run the migrations.
		 *
		 * @return void
		 */
		public function up()
		{
			Schema::create('cover_types', function (Blueprint $table) {
				$table->id(); // Equivalent to INT UNSIGNED AUTO_INCREMENT PRIMARY KEY (BIGINT in Laravel)
				$table->string('type_name', 100)->unique('type_name_unique');
				// No need for $table->timestamps(); if not present in SQL
				// Table collation and engine are usually set at connection level or can be specified:
				// $table->engine = 'InnoDB';
				// $table->charset = 'utf8mb4';
				// $table->collation = 'utf8mb4_unicode_ci';
			});
		}

		/**
		 * Reverse the migrations.
		 *
		 * @return void
		 */
		public function down()
		{
			Schema::dropIfExists('cover_types');
		}
	};
