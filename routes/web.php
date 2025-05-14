<?php

	use App\Http\Controllers\Admin\DashboardController;
	use App\Http\Controllers\HomeController;
	use App\Http\Controllers\ProfileController;
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

	Route::prefix('admin')->name('admin.')->group(function () {
		// Route::middleware(['auth'])->group(function () { // Uncomment to protect admin routes
		Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

		// API-like routes for admin actions
		Route::get('/cover-types', [DashboardController::class, 'listCoverTypes'])->name('cover-types.list');
		Route::get('/items', [DashboardController::class, 'listItems'])->name('items.list');
		Route::post('/items/upload', [DashboardController::class, 'uploadItem'])->name('items.upload');
		Route::get('/items/details', [DashboardController::class, 'getItemDetails'])->name('items.details');
		Route::post('/items/update', [DashboardController::class, 'updateItem'])->name('items.update');
		Route::post('/items/delete', [DashboardController::class, 'deleteItem'])->name('items.delete');
		Route::post('/items/generate-ai-metadata', [DashboardController::class, 'generateAiMetadata'])->name('items.generate-ai-metadata');
		Route::post('/templates/generate-similar', [DashboardController::class, 'generateSimilarTemplate'])->name('templates.generate-similar');

		// New routes for Cover-Template assignments
		Route::get('/covers/{cover}/assignable-templates', [DashboardController::class, 'listAssignableTemplates'])->name('covers.list-assignable-templates');
		Route::post('/covers/{cover}/assign-templates', [DashboardController::class, 'updateCoverTemplateAssignments'])->name('covers.update-assignments');

		// }); // End auth middleware group
	});


	Route::get('/', [HomeController::class, 'index'])->name('home');
	Route::get('/api/genres/{genreSlug}/covers', [HomeController::class, 'getCoversForGenre'])->name('api.genres.covers');

	Route::get('/shop', function () {
		return "Shop Page (Covers Index)";
	})->name('shop.index');

	Route::get('/blog', function () {
		return "Blog Page";
	})->name('blog.index');

	Route::get('/covers/{cover}', function (App\Models\Cover $cover) {
		return "Cover Details Page for: " . $cover->name;
	})->name('covers.show');


	Route::get('/dashboard', function () {
		return view('dashboard');
	})->middleware(['auth', 'verified'])->name('dashboard');

	Route::middleware('auth')->group(function () {
		Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
		Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
		Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
	});

	require __DIR__.'/auth.php';
