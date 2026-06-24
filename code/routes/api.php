<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ========== Public Routes ==========
Route::post('/auth/login', [ApiController::class, 'login']);
Route::post('/auth/register', [ApiController::class, 'register']);

// GitHub OAuth redirect (public)
Route::get('/auth/github/redirect', [ApiController::class, 'githubRedirect'])->name('github.redirect');

// GitHub OAuth Callback (public - কিন্তু user session থেকে পাবেন)
Route::get('/auth/github/callback', [ApiController::class, 'githubCallback']);

// ========== Protected Routes (require authentication) ==========
Route::middleware('auth:sanctum')->group(function () {

    // ---- Authentication ----
    Route::post('/auth/logout', [ApiController::class, 'logout']);
    Route::get('/auth/user', [ApiController::class, 'user']);

    // ---- Profile Management ----
    Route::put('/auth/profile', [ApiController::class, 'updateProfile']);
    Route::post('/auth/change-password', [ApiController::class, 'changePassword']);

    // ---- Student Dashboard ----
    Route::get('/student/dashboard', [ApiController::class, 'getStudentDashboard']);

    // ---- GitHub Routes ----
    Route::get('/github/redirect', [ApiController::class, 'githubOAuthRedirect']);
    Route::get('/github/repo-details', [ApiController::class, 'getGitHubRepoDetails']);
    Route::post('/github/connect', [ApiController::class, 'connectGitHub']);
    Route::post('/github/sync', [ApiController::class, 'syncGitHub']);
    Route::delete('/github/disconnect', [ApiController::class, 'disconnectGitHub']);

    // ---- Courses ----
    Route::get('/courses', [ApiController::class, 'getCourses']);
    Route::get('/courses/professor/{id}', [ApiController::class, 'getProfessorCourses']);
    Route::get('/courses/student/{id}', [ApiController::class, 'getStudentCourses']);
    Route::post('/courses/create', [ApiController::class, 'createCourse']);
    Route::put('/courses/{id}', [ApiController::class, 'updateCourse']);
    Route::post('/courses/{courseId}/enroll', [ApiController::class, 'enrollStudents']);
    Route::post('/courses/enroll-via-code', [ApiController::class, 'enrollViaCode']);
    Route::get('/courses/{id}/students', [ApiController::class, 'getCourseStudents']);

    // ---- Groups ----
    Route::get('/groups', [ApiController::class, 'getGroups']);
    Route::get('/groups/{id}', [ApiController::class, 'getGroup']);
    Route::get('/groups/{id}/members', [ApiController::class, 'getGroupMembers']);
    Route::get('/user/groups', [ApiController::class, 'getUserGroups']);
    Route::post('/groups/create', [ApiController::class, 'createGroup']);
    Route::post('/groups/join', [ApiController::class, 'joinGroup']);
    Route::delete('/groups/{id}/leave', [ApiController::class, 'leaveGroup']);
    Route::delete('/groups/{id}', [ApiController::class, 'deleteGroup']);

    // ---- Peer Reviews ----
    Route::post('/peer-reviews/submit', [ApiController::class, 'submitPeerReview']);
    Route::get('/peer-reviews/status/{groupId}', [ApiController::class, 'getPeerReviewStatus']);
    Route::get('/peer-reviews/student/{studentId}', [ApiController::class, 'getReviewsForStudent']);

    // ---- Contribution Scores ----
    Route::post('/contribution/calculate/{studentId}/{assignmentId}', [ApiController::class, 'calculateContributionScore']);
    Route::get('/contribution/score/{studentId}/{assignmentId}', [ApiController::class, 'getStudentScore']);

    // ---- Analytics ----
    Route::get('/analytics/student/{id}', [ApiController::class, 'getStudentAnalytics']);
    Route::get('/analytics/group/{id}', [ApiController::class, 'getGroupAnalytics']);

    // ---- AI Evaluation ----
    Route::get('/ai/evaluate/student/{id}', [ApiController::class, 'evaluateStudent']);

    // ---- Notifications ----
    Route::get('/notifications', [ApiController::class, 'getNotifications']);
    Route::post('/notifications/{id}/read', [ApiController::class, 'markNotificationRead']);

    // ---- Reports ----
    Route::post('/reports/generate', [ApiController::class, 'generateReport']);

    // ---- Attendance ----
    Route::post('/attendance/mark', [ApiController::class, 'markAttendance']);
    Route::post('/attendance/get', [ApiController::class, 'getStudentAttendance']);
    Route::get('/attendance/course/{courseId}', [ApiController::class, 'getCourseAttendance']);
});