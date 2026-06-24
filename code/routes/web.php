<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home page
Route::get('/', function () {
    return view('app');
});
// Admin login page
Route::get('/admin/login.html', function () {
    $path = public_path("frontend/pages/admin/login.html");
    return file_exists($path) ? response()->file($path) : view('app');
});

// Admin dashboard page
Route::get('/admin/dashboard.html', function () {
    $path = public_path("frontend/pages/admin/dashboard.html");
    return file_exists($path) ? response()->file($path) : view('app');
});
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
    if (file_exists($path)) {
        return response()->file($path);
    }
    return view('app');
});

// Serve professor pages
Route::get('/professor/{page}.html', function ($page) {
    $path = public_path("frontend/pages/professor/{$page}.html");
    if (file_exists($path)) {
        return response()->file($path);
    }
    return view('app');
});

// Serve CSS files
Route::get('/styles/{file}.css', function ($file) {
    $path = public_path("frontend/styles/{$file}.css");
    if (file_exists($path)) {
        return response()->file($path, ['Content-Type' => 'text/css']);
    }
    abort(404);
});

// Serve JavaScript files
Route::get('/{folder}/{file}.js', function ($folder, $file) {
    $path = public_path("frontend/{$folder}/{$file}.js");
    if (file_exists($path)) {
        return response()->file($path, ['Content-Type' => 'application/javascript']);
    }
    abort(404);
})->where('folder', 'services|utils|store|config|components|layouts|pages');

// API Routes (prefix: /api)
Route::prefix('api')->group(base_path('routes/api.php'));

// SPA catch-all route (must be last)
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/auth/github/redirect', function () {
    return redirect('https://github.com/login/oauth/authorize?client_id=YOUR_CLIENT_ID&redirect_uri=' . urlencode(url('/auth/github/callback')));
})->name('github.redirect');