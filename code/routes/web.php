<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| Web Routes (Optimized Hybrid Monolith)
|--------------------------------------------------------------------------
*/

// Home page
Route::get('/', function () {
    return view('app');
});

// Serve Admin pages (Refactored into a single clean route)
Route::get('/admin/{page}.html', function ($page) {
    $path = public_path("frontend/pages/admin/{$page}.html");
    return file_exists($path) ? response()->file($path) : view('app');
})->where('page', 'login|dashboard');

// Serve public HTML pages (login, register, landing)
Route::get('/{page}.html', function ($page) {
    $paths = [
        public_path("frontend/pages/public/{$page}.html"),
        public_path("frontend/{$page}.html")
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            return response()->file($path);
        }
    }
    return view('app');
});

// Serve student pages
Route::get('/student/{page}.html', function ($page) {
    $path = public_path("frontend/pages/student/{$page}.html");
    return file_exists($path) ? response()->file($path) : view('app');
});

// Serve professor pages
Route::get('/professor/{page}.html', function ($page) {
    $path = public_path("frontend/pages/professor/{$page}.html");
    return file_exists($path) ? response()->file($path) : view('app');
});

// Serve CSS files
Route::get('/styles/{file}.css', function ($file) {
    $path = public_path("frontend/styles/{$file}.css");
    return file_exists($path) ? response()->file($path, ['Content-Type' => 'text/css']) : abort(404);
});

// Serve JavaScript files
Route::get('/{folder}/{file}.js', function ($folder, $file) {
    $path = public_path("frontend/{$folder}/{$file}.js");
    return file_exists($path) ? response()->file($path, ['Content-Type' => 'application/javascript']) : abort(404);
})->where('folder', 'services|utils|store|config|components|layouts|pages');

// API Routes handoff
Route::prefix('api')->group(base_path('routes/api.php'));

// ============================================
// GITHUB OAUTH ROUTES (WEB - Stateful Session)
// ============================================

Route::get('/auth/github/redirect', [ApiController::class, 'githubRedirect'])->name('github.redirect');
Route::get('/auth/github/callback', [ApiController::class, 'githubCallback'])->name('github.callback');

// SPA catch-all route (must be last)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
// ============================================
// GITHUB OAUTH ROUTES (WEB - Stateful Session)
// ============================================

Route::get('/auth/github/redirect', [ApiController::class, 'githubRedirect'])->name('github.redirect');
Route::get('/auth/github/callback', [ApiController::class, 'githubCallback'])->name('github.callback');

// GitHub Repo Save & Disconnect (Auth Middleware এর ভেতরে রাখতে হবে)
Route::middleware('auth')->group(function () {
    Route::post('/api/github/save-repo', [ApiController::class, 'saveRepoUrl']);
    Route::post('/api/github/disconnect', [ApiController::class, 'disconnectGithub']);
    
    // ফ্রন্টএন্ড থেকে ইউজারের গিটহাব স্ট্যাটাস চেক করার জন্য একটি API
    Route::get('/api/user/github-status', [ApiController::class, 'checkGithubStatus']);
});