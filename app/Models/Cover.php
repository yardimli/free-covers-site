<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
		'cover_path',
		'cover_thumbnail_path',
		'mockup_2d_path',
		'has_real_2d',
		'mockup_3d_path',
		'has_real_3d',
		'full_cover_path',
		'full_cover_thumbnail_path',
		'caption',
		'keywords',
		'categories',
		'text_placements',
	];

	/**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'keywords' => 'array',
		'categories' => 'array',
		'text_placements' => 'array',
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

	public function favorites(): HasMany
	{
		return $this->hasMany(Favorite::class);
	}

	// Helper to get all image paths for deletion
	public function getAllImagePaths(): array
	{
		return array_filter([
			$this->cover_path,
			$this->cover_thumbnail_path,
			$this->mockup_2d_path,
			$this->mockup_3d_path,
			$this->full_cover_path,
			$this->full_cover_thumbnail_path,
		]);
	}
}
