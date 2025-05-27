<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	class Favorite extends Model
	{
		use HasFactory;

		protected $fillable = [
			'user_id',
			'cover_id',
			'template_id',
		];

		public function user(): BelongsTo
		{
			return $this->belongsTo(User::class);
		}

		public function cover(): BelongsTo
		{
			return $this->belongsTo(Cover::class);
		}

		public function template(): BelongsTo
		{
			return $this->belongsTo(Template::class); // template_id is nullable
		}
	}
