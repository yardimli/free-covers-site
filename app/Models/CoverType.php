<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	class CoverType extends Model
	{
		use HasFactory;

		/**
		 * The table associated with the model.
		 *
		 * @var string
		 */
		protected $table = 'cover_types';

		/**
		 * Indicates if the model should be timestamped.
		 *
		 * @var bool
		 */
		public $timestamps = false; // As per your SQL schema

		/**
		 * The attributes that are mass assignable.
		 *
		 * @var array<int, string>
		 */
		protected $fillable = [
			'type_name',
		];

		/**
		 * Get the covers associated with the cover type.
		 */
		public function covers(): HasMany
		{
			return $this->hasMany(Cover::class);
		}

		/**
		 * Get the templates associated with the cover type.
		 */
		public function templates(): HasMany
		{
			return $this->hasMany(Template::class);
		}
	}
