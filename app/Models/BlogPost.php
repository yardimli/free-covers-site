<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	class BlogPost extends Model
	{
		use HasFactory;

		protected $fillable = [
			'blog_category_id',
			'title',
			'slug',
			'short_description',
			'content',
			'keywords',
			'image_path',
			'thumbnail_path',
			'status',
			'published_at',
		];

		protected $casts = [
			'keywords' => 'array',
			'published_at' => 'datetime',
		];

		public function category(): BelongsTo
		{
			return $this->belongsTo(BlogCategory::class, 'blog_category_id');
		}

		// Helper to get all image paths for deletion
		public function getAllImagePaths(): array
		{
			return array_filter([
				$this->image_path,
				$this->thumbnail_path,
			]);
		}
	}
