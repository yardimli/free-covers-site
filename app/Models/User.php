<?php

	namespace App\Models;

	// use Illuminate\Contracts\Auth\MustVerifyEmail;
	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Relations\HasMany;
	use Illuminate\Foundation\Auth\User as Authenticatable;
	use Illuminate\Notifications\Notifiable;
	use Laravel\Sanctum\HasApiTokens;

	class User extends Authenticatable // implements MustVerifyEmail // If you implement email verification
	{
		use HasApiTokens, HasFactory, Notifiable;

		public const TYPE_USER = 1;
		public const TYPE_ADMIN = 2;

		/**
		 * The attributes that are mass assignable.
		 *
		 * @var array<int, string>
		 */
		protected $fillable = [
			'name',
			'email',
			'password',
			'user_type', // Add this
		];

		/**
		 * The attributes that should be hidden for serialization.
		 *
		 * @var array<int, string>
		 */
		protected $hidden = [
			'password',
			'remember_token',
		];

		/**
		 * The attributes that should be cast.
		 *
		 * @var array<string, string>
		 */
		protected $casts = [
			'email_verified_at' => 'datetime',
			'password' => 'hashed',
			'user_type' => 'integer', // Add this cast
		];

		/**
		 * Check if the user is an admin.
		 *
		 * @return bool
		 */
		public function isAdmin(): bool
		{
			return $this->user_type === self::TYPE_ADMIN;
		}

		public function favorites(): HasMany
		{
			return $this->hasMany(Favorite::class);
		}

		public function userDesigns(): HasMany
		{
			return $this->hasMany(UserDesign::class);
		}
	}
