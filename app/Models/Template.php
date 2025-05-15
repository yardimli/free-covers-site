<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Add this

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
		'json_path', // Note: This field seems unused if json_content is primary
		'json_content',
		'keywords',
		'text_placements', // Added
	];

	/**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'json_content' => 'array', // Or 'object' if you prefer stdClass
		'keywords' => 'array',
		'text_placements' => 'array', // Added
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

	/**
	 * The covers that belong to the template.
	 */
	public function covers(): BelongsToMany
	{
		return $this->belongsToMany(Cover::class, 'cover_template', 'template_id', 'cover_id');
	}
}
