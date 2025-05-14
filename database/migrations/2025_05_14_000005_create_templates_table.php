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
			Schema::create('templates', function (Blueprint $table) {
				$table->id(); // INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY

				// Foreign key: cover_type_id INT(11) UNSIGNED NULL DEFAULT NULL
				$table->foreignId('cover_type_id')->nullable()->constrained('cover_types')->onDelete('set null');

				$table->string('name'); // VARCHAR(255) NOT NULL
				$table->string('thumbnail_path', 512); // VARCHAR(512) NOT NULL
				$table->string('json_path', 512)->nullable(); // VARCHAR(512) NULL DEFAULT NULL
				$table->json('json_content'); // LONGTEXT NOT NULL, CHECK (json_valid(json_content))
				$table->json('keywords')->nullable()->default('[]'); // TEXT NULL DEFAULT '[]' (using json type is better)

				$table->timestamp('created_at')->useCurrent();
				$table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
				// Or simply: $table->timestamps();

				$table->index('name', 'idx_templates_name');

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
			Schema::dropIfExists('templates');
		}
	};
