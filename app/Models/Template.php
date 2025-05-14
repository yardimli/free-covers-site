<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	class Template extends Model
	{
		use HasFactory;

		/**
		 * The table associated with the model.
		 *
		 * @var string
		 */
		protected $table = 'templates';

		/**
		 * The attributes that are mass assignable.
		 *
		 * @var array<int, string>
		 */
		protected $fillable = [
			'cover_type_id',
			'name',
			'thumbnail_path',
			'json_path',
			'json_content',
			'keywords',
		];

		/**
		 * The attributes that should be cast.
		 *
		 * @var array<string, string>
		 */
		protected $casts = [
			'json_content' => 'array', // Or 'object' if you prefer stdClass
			'keywords' => 'array',
			'created_at' => 'datetime',
			'updated_at' => 'datetime',
		];

		/**
		 * Get the cover type that owns the template.
		 */
		public function coverType(): BelongsTo
		{
			return $this->belongsTo(CoverType::class, 'cover_type_id');
		}
	}
