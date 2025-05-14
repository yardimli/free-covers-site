<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CoverTemplate extends Model
{
	use HasFactory;

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'cover_template';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'cover_id',
		'template_id',
	];

	function cover(): BelongsTo
	{
		return $this->belongsTo(Cover::class, 'cover_id');
	}

	function template(): BelongsTo
	{
		return $this->belongsTo(Template::class, 'template_id');
	}
}
