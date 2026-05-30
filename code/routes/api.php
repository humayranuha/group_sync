<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

Route::post('/auth/login', [ApiController::class, 'login']);
Route::post('/auth/register', [ApiController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [ApiController::class, 'logout']);
    Route::get('/auth/user', [ApiController::class, 'user']);
    
    Route::get('/courses', [ApiController::class, 'getCourses']);
    Route::get('/groups', [ApiController::class, 'getGroups']);
    Route::get('/analytics/student/{id}', [ApiController::class, 'getStudentAnalytics']);
    Route::get('/analytics/group/{id}', [ApiController::class, 'getGroupAnalytics']);
    Route::get('/ai/evaluate/student/{id}', [ApiController::class, 'evaluateStudent']);
});