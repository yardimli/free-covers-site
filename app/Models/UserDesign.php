<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	class UserDesign extends Model
	{
		use HasFactory;

		protected $fillable = [
			'user_id',
			'name',
			'json_data',
			'preview_image_path',
		];

		protected $casts = [
			'json_data' => 'array',
			'created_at' => 'datetime',
			'updated_at' => 'datetime',
		];

		public function user(): BelongsTo
		{
			return $this->belongsTo(User::class);
		}
	}
