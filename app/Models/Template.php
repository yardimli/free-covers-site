<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
		'cover_image_path',                 // Renamed from thumbnail_path
		'json_content',                     // Existing
		'keywords',
		'text_placements',
		'full_cover_image_path',            // New
		'full_cover_image_thumbnail_path',  // New
		'full_cover_json_content',          // New
	];

	/**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'json_content' => 'array',
		'full_cover_json_content' => 'array', // New
		'keywords' => 'array',
		'text_placements' => 'array',
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

	// Helper to get all image paths for deletion
	public function getAllImagePaths(): array
	{
		return array_filter([
			$this->cover_image_path,
			$this->full_cover_image_path,
			$this->full_cover_image_thumbnail_path,
		]);
	}
}
