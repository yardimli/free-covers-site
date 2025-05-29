<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('user_designs', function (Blueprint $table) {
				$table->id();
				$table->foreignId('user_id')->constrained()->onDelete('cascade');
				$table->string('name')->default('Untitled Design');
				$table->text('json_data');
				$table->string('preview_image_path');
				$table->timestamps();
			});
		}

		public function down(): void
		{
			Schema::dropIfExists('user_designs');
		}
	};
