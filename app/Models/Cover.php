<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cover extends Model
{
	use HasFactory;

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'covers';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'cover_type_id',
		'name',
		'thumbnail_path',
		'image_path',
		'caption',
		'keywords',
		'categories',
		'text_placements', // Added
	];

	/**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'keywords' => 'array',
		'categories' => 'array',
		'text_placements' => 'array', // Added
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	];

	/**
	 * Get the cover type that owns the cover.
	 */
	public function coverType(): BelongsTo
	{
		return $this->belongsTo(CoverType::class, 'cover_type_id');
	}

	/**
	 * The templates that belong to the cover.
	 */
	public function templates(): BelongsToMany
	{
		return $this->belongsToMany(Template::class, 'cover_template', 'cover_id', 'template_id');
	}
}
