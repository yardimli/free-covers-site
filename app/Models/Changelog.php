<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Factories\HasFactory;
	use Illuminate\Database\Eloquent\Model;

	class Changelog extends Model
	{
		use HasFactory;

		protected $fillable = [
			'commit_hash',
			'commit_date',
			'author_name',
			'author_email',
			'message',
			'hide',
		];

		protected $casts = [
			'commit_date' => 'datetime',
			'hide' => 'boolean',
		];
	}
