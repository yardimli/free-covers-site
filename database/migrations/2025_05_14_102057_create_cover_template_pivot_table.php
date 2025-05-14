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
			Schema::create('cover_template', function (Blueprint $table) {
				$table->foreignId('cover_id')->constrained('covers')->onDelete('cascade');
				$table->foreignId('template_id')->constrained('templates')->onDelete('cascade');
				$table->primary(['cover_id', 'template_id']);
				// No timestamps needed for a simple pivot table
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('cover_template');
		}
	};
