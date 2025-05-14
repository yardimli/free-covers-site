<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class Element extends Model
	{
		use HasFactory;

		/**
		 * The table associated with the model.
		 *
		 * @var string
		 */
		protected $table = 'elements';

		/**
		 * The attributes that are mass assignable.
		 *
		 * @var array<int, string>
		 */
		protected $fillable = [
			'name',
			'thumbnail_path',
			'image_path',
			'keywords',
		];

		/**
		 * The attributes that should be cast.
		 *
		 * @var array<string, string>
		 */
		protected $casts = [
			'keywords' => 'array', // For JSON columns
			'created_at' => 'datetime',
			'updated_at' => 'datetime',
		];
	}
