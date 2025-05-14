<?php

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

	Route::get('/', [HomeController::class, 'index'])->name('home');

// AJAX endpoint for fetching covers by genre
	Route::get('/api/genres/{genreSlug}/covers', [HomeController::class, 'getCoversForGenre'])->name('api.genres.covers');


	Route::get('/shop', function () {
		// Placeholder for shop page
		return "Shop Page (Covers Index)";
	})->name('shop.index'); // Corresponds to "Browse Covers"

	Route::get('/blog', function () {
		// Placeholder for blog page
		return "Blog Page";
	})->name('blog.index');

	Route::get('/covers/{cover}', function (App\Models\Cover $cover) {
		// Placeholder for a single cover page
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

	require __DIR__ . '/auth.php';
