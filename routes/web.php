<?php

	use App\Http\Controllers\Admin\DashboardController;
	use App\Http\Controllers\CoverController;
	use App\Http\Controllers\HomeController;
	use App\Http\Controllers\ProfileController;
	use App\Http\Controllers\ShopController;
	use Illuminate\Support\Facades\Route;

	/*
	|--------------------------------------------------------------------------
	| Web Routes
	|--------------------------------------------------------------------------
	|
	| Here is where you can register web routes for your application. These
	| routes are loaded by the RouteServiceProvider and all of them will
	| be assigned to the "web" middleware group. Make something great!
	|
	*/

	Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
		// Route::middleware(['auth'])->group(function () { // Uncomment to protect admin routes
		Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

		// API-like routes for admin actions
		Route::get('/cover-types', [DashboardController::class, 'listCoverTypes'])->name('cover-types.list');
		Route::get('/items', [DashboardController::class, 'listItems'])->name('items.list');
		Route::post('/items/upload', [DashboardController::class, 'uploadItem'])->name('items.upload');
		Route::get('/items/details', [DashboardController::class, 'getItemDetails'])->name('items.details');
		Route::post('/items/update', [DashboardController::class, 'updateItem'])->name('items.update');
		Route::post('/items/delete', [DashboardController::class, 'deleteItem'])->name('items.delete');

		Route::get('/covers/needing-metadata', [DashboardController::class, 'getCoversNeedingMetadata'])->name('covers.needing-metadata');
		Route::post('/items/generate-ai-metadata', [DashboardController::class, 'generateAiMetadata'])->name('items.generate-ai-metadata');

		Route::post('/templates/generate-similar', [DashboardController::class, 'generateSimilarTemplate'])->name('templates.generate-similar');

		Route::post('/covers/{cover}/generate-ai-text-placements', [DashboardController::class, 'generateAiTextPlacements'])->name('covers.generate-ai-text-placements');
		Route::get('/covers/unprocessed-for-text-placement', [DashboardController::class, 'getUnprocessedCoversForTextPlacement'])->name('covers.unprocessed-list');


		// New routes for Cover-Template assignments
		Route::get('/covers/{cover}/assignable-templates', [DashboardController::class, 'listAssignableTemplates'])->name('covers.list-assignable-templates');
		Route::post('/covers/{cover}/assign-templates', [DashboardController::class, 'updateCoverTemplateAssignments'])->name('covers.update-assignments');

		Route::post('/items/{item_type}/{id}/update-text-placements', [DashboardController::class, 'updateTextPlacements'])->name('items.update-text-placements');

		Route::get('/covers/without-templates', [DashboardController::class, 'getCoversWithoutTemplates'])->name('covers.without-templates');
		Route::post('/covers/{cover}/templates/{template}/ai-evaluate-fit', [DashboardController::class, 'aiEvaluateTemplateFit'])->name('covers.templates.ai-evaluate-fit');

		Route::post('/covers/{cover}/templates/{template}/remove', [DashboardController::class, 'removeCoverTemplateAssignment'])->name('covers.templates.remove-assignment');

		// }); // End auth middleware group
	});


	Route::get('/', [HomeController::class, 'index'])->name('home');
	Route::get('/api/genres/{genreSlug}/covers', [HomeController::class, 'getCoversForGenre'])->name('api.genres.covers');

	Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');


	Route::get('/blog', function () {
		return "Blog Page";
	})->name('blog.index');

	Route::get('/covers/{cover}', [CoverController::class, 'show'])->name('covers.show');



	Route::get('/dashboard', function () {
		return view('dashboard');
	})->middleware(['auth', 'verified'])->name('dashboard');

	Route::middleware('auth')->group(function () {
		Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
		Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
		Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
	});

	require __DIR__.'/auth.php';
