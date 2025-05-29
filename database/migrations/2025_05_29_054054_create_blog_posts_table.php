<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('blog_posts', function (Blueprint $table) {
				$table->id();
				$table->foreignId('blog_category_id')->constrained('blog_categories')->onDelete('cascade');
				$table->string('title');
				$table->string('slug')->unique();
				$table->text('short_description')->nullable();
				$table->longText('content');
				$table->json('keywords')->nullable();
				$table->string('image_path')->nullable();
				$table->string('thumbnail_path')->nullable();
				$table->enum('status', ['draft', 'published'])->default('draft');
				$table->timestamp('published_at')->nullable();
				$table->timestamps();
			});
		}

		public function down(): void
		{
			Schema::dropIfExists('blog_posts');
		}
	};
