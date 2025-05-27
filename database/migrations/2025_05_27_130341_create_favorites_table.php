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
			Schema::create('favorites', function (Blueprint $table) {
				$table->id();
				$table->foreignId('user_id')->constrained()->onDelete('cascade');
				$table->foreignId('cover_id')->constrained()->onDelete('cascade');
				$table->foreignId('template_id')->constrained()->onDelete('cascade');
				$table->timestamps();

				// Ensure a user can't favorite the exact same item (cover + specific template, or cover + no template) multiple times
				$table->unique(['user_id', 'cover_id', 'template_id']);
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('favorites');
		}
	};
