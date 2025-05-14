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
			Schema::create('elements', function (Blueprint $table) {
				$table->id(); // INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
				$table->string('name'); // VARCHAR(255) NOT NULL
				$table->string('thumbnail_path', 512); // VARCHAR(512) NOT NULL
				$table->string('image_path', 512); // VARCHAR(512) NOT NULL
				$table->json('keywords')->nullable(); // LONGTEXT NULL DEFAULT NULL, CHECK (json_valid(keywords))

				$table->timestamp('created_at')->useCurrent();
				$table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
				// Or simply: $table->timestamps();

				$table->index('name', 'idx_elements_name');

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
			Schema::dropIfExists('elements');
		}
	};
