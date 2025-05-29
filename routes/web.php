<?php

	use App\Http\Controllers\Admin\AdminAIController;
	use App\Http\Controllers\Admin\BlogManagementController;
	use App\Http\Controllers\Admin\AdminDashboardController;
	use App\Http\Controllers\BlogController;
	use App\Http\Controllers\CoverController;
	use App\Http\Controllers\DesignerController;
	use App\Http\Controllers\FavoriteController;
	use App\Http\Controllers\HomeController;
	use App\Http\Controllers\ProfileController;
	use App\Http\Controllers\ShopController;
	use App\Http\Controllers\UserDesignController;
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
		Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

		// API-like routes for admin actions
		Route::get('/cover-types', [AdminDashboardController::class, 'listCoverTypes'])->name('cover-types.list');
		Route::get('/items', [AdminDashboardController::class, 'listItems'])->name('items.list');
		Route::post('/items/upload', [AdminDashboardController::class, 'uploadItem'])->name('items.upload');
		Route::get('/items/details', [AdminDashboardController::class, 'getItemDetails'])->name('items.details');
		Route::post('/items/update', [AdminDashboardController::class, 'updateItem'])->name('items.update');
		Route::post('/items/delete', [AdminDashboardController::class, 'deleteItem'])->name('items.delete');

		Route::post('/covers/upload-zip', [AdminDashboardController::class, 'uploadCoverZip'])->name('covers.upload-zip');

		Route::get('/covers/needing-metadata', [AdminAIController::class, 'getCoversNeedingMetadata'])->name('covers.needing-metadata');
		Route::post('/items/generate-ai-metadata', [AdminAIController::class, 'generateAiMetadata'])->name('items.generate-ai-metadata');

		Route::post('/templates/generate-similar', [AdminAIController::class, 'generateSimilarTemplate'])->name('templates.generate-similar');

		Route::post('/covers/{cover}/generate-ai-text-placements', [AdminAIController::class, 'generateAiTextPlacements'])->name('covers.generate-ai-text-placements');
		Route::get('/covers/unprocessed-for-text-placement', [AdminAIController::class, 'getUnprocessedCoversForTextPlacement'])->name('covers.unprocessed-list');


		// New routes for Cover-Template assignments
		Route::get('/covers/{cover}/assignable-templates', [AdminAIController::class, 'listAssignableTemplates'])->name('covers.list-assignable-templates');
		Route::post('/covers/{cover}/assign-templates', [AdminAIController::class, 'updateCoverTemplateAssignments'])->name('covers.update-assignments');

		Route::post('/items/{item_type}/{id}/update-text-placements', [AdminAIController::class, 'updateTextPlacements'])->name('items.update-text-placements');

		Route::get('/covers/without-templates', [AdminAIController::class, 'getCoversWithoutTemplates'])->name('covers.without-templates');
		Route::post('/covers/{cover}/templates/{template}/ai-evaluate-fit', [AdminAIController::class, 'aiEvaluateTemplateFit'])->name('covers.templates.ai-evaluate-fit');

		Route::get('/cover-template-management', [AdminAIController::class, 'coverTemplateManagementIndex'])->name('covers.template-management.index');
		Route::post('/covers/{cover}/templates/{template}/remove', [AdminAIController::class, 'removeCoverTemplateAssignment'])->name('covers.templates.remove-assignment');

		Route::post('/templates/{template}/update-json', [AdminAIController::class, 'updateTemplateJson'])->name('templates.update-json');

		Route::post('/templates/{template}/generate-full-cover-json', [AdminAIController::class, 'generateFullCoverJsonForTemplate'])->name('templates.generate-full-cover-json');


		Route::prefix('blog')->name('blog.')->group(function () {
			Route::get('/', [BlogManagementController::class, 'index'])->name('index');

			// AI Post Generation (endpoint remains, UI trigger will move or be re-evaluated)
			Route::post('/posts/generate-ai', [BlogManagementController::class, 'generateAiBlogPost'])->name('posts.generate-ai');

			// Categories (no changes here)
			Route::get('/categories', [BlogManagementController::class, 'listCategories'])->name('categories.list');
			Route::post('/categories', [BlogManagementController::class, 'storeCategory'])->name('categories.store');
			Route::put('/categories/{category}', [BlogManagementController::class, 'updateCategory'])->name('categories.update');
			Route::delete('/categories/{category}', [BlogManagementController::class, 'destroyCategory'])->name('categories.destroy');

			// Posts - Updated for page-based CRUD
			Route::get('/posts', [BlogManagementController::class, 'listPosts'])->name('posts.list'); // List remains AJAX populated
			Route::get('/posts/create', [BlogManagementController::class, 'create'])->name('posts.create'); // Page to create a post
			Route::post('/posts', [BlogManagementController::class, 'storePost'])->name('posts.store'); // Form submission for create
			Route::get('/posts/{post}/edit', [BlogManagementController::class, 'edit'])->name('posts.edit'); // Page to edit a post
			Route::post('/posts/{post}', [BlogManagementController::class, 'updatePost'])->name('posts.update'); // Form submission for update (using POST for simplicity with @method)
			Route::delete('/posts/{post}', [BlogManagementController::class, 'destroyPost'])->name('posts.destroy'); // Delete remains AJAX
		});
	});


	Route::get('/', [HomeController::class, 'index'])->name('home');
	Route::get('/api/genres/{genreSlug}/covers', [HomeController::class, 'getCoversForGenre'])->name('api.genres.covers');

	Route::get('/browse-covers', [ShopController::class, 'index'])->name('shop.index');
	Route::get('/about-us', [HomeController::class, 'about'])->name('about');
	Route::get('/terms-and-conditions', function () {
		return view('terms');
	})->name('terms');

	Route::get('/privacy-policy', function () {
		return view('privacy');
	})->name('privacy');

	Route::get('/contact-us', [HomeController::class, 'showContactForm'])->name('contact.show');
	Route::post('/contact-us', [HomeController::class, 'submitContactForm'])->name('contact.submit');

	Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
	Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

	Route::get('/covers/{cover}/{template?}', [CoverController::class, 'show'])->name('covers.show');

	Route::get('/api/templates/{template}/json', [DesignerController::class, 'getTemplateJsonData'])->name('api.templates.json_data');


	Route::middleware('auth')->group(function () {
		Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

		Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
		Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
		Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

		Route::get('/designer/setup-canvas', [DesignerController::class, 'setupCanvas'])->name('designer.setup');
		Route::get('/designer', [DesignerController::class, 'index'])->name('designer.index');

		// Favorite Routes
		Route::post('/favorites', [FavoriteController::class, 'store'])->name('favorites.store');
		Route::delete('/favorites', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
		Route::delete('/favorites/{favorite}', [FavoriteController::class, 'destroyById'])->name('favorites.destroyById');

		Route::prefix('user-designs')->name('user-designs.')->group(function () {
			Route::post('/', [UserDesignController::class, 'store'])->name('store');
			Route::get('/{userDesign}/json', [UserDesignController::class, 'getJsonData'])->name('json_data');
			Route::delete('/{userDesign}', [UserDesignController::class, 'destroy'])->name('destroy');
			// Add update route if needed later for renaming etc.
			// Route::put('/{userDesign}', [UserDesignController::class, 'update'])->name('update');
		});

	});


	require __DIR__.'/auth.php';
