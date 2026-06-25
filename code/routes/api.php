<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ========== Public Routes ==========

// ---- Authentication ----
Route::post('/auth/login', [ApiController::class, 'login']);
Route::post('/auth/register', [ApiController::class, 'register']);

// ---- GitHub OAuth (Public Callback Only) ----
Route::get('/auth/github/callback', [ApiController::class, 'githubCallback'])->name('github.callback');

// ---- Test Routes ----
Route::get('/test-slackers', [ApiController::class, 'getLowContributors']);


// ========== Protected Routes (require authentication) ==========
Route::middleware('auth:sanctum')->group(function () {

    // ---- User Session ----
    Route::post('/auth/logout', [ApiController::class, 'logout']);
    Route::get('/auth/user', [ApiController::class, 'user']);

    // ---- Profile Management ----
    Route::put('/auth/profile', [ApiController::class, 'updateProfile']);
    Route::post('/auth/change-password', [ApiController::class, 'changePassword']);

    // ---- Student Dashboard ----
    Route::get('/student/dashboard', [ApiController::class, 'getStudentDashboard']);

    // ---- GitHub Routes (Protected actions) ----
    Route::get('/github/redirect', [ApiController::class, 'githubOAuthRedirect']); // <-- নতুন সিকিউর রাউট এখানে যুক্ত হলো
    Route::post('/github/connect', [ApiController::class, 'connectGitHub']);
    Route::post('/github/disconnect', [ApiController::class, 'disconnectGitHub']);
    Route::post('/github/sync', [ApiController::class, 'syncGitHub']);
    Route::get('/github/repo-details', [ApiController::class, 'getGitHubRepoDetails']);

    // ---- Courses ----
    Route::get('/courses', [ApiController::class, 'getCourses']);
    Route::get('/courses/professor/{id}', [ApiController::class, 'getProfessorCourses']);
    Route::get('/courses/student/{id}', [ApiController::class, 'getStudentCourses']);
    Route::post('/courses/create', [ApiController::class, 'createCourse']);
    Route::put('/courses/{id}', [ApiController::class, 'updateCourse']);
    Route::post('/courses/{courseId}/enroll', [ApiController::class, 'enrollStudents']);
    Route::post('/courses/enroll-via-code', [ApiController::class, 'enrollViaCode']);
    Route::get('/courses/{id}/students', [ApiController::class, 'getCourseStudents']);
    Route::post('/courses/{courseId}/enroll-students', [ApiController::class, 'enrollStudentsToCourse']); 
    Route::delete('/courses/{courseId}/students/{studentId}', [ApiController::class, 'removeStudentFromCourse']); 
    Route::get('/courses/{courseId}/enrollable-students', [ApiController::class, 'getEnrollableStudents']); 

    // ---- Groups ----
    Route::get('/groups', [ApiController::class, 'getGroups']);
    Route::get('/groups/{id}', [ApiController::class, 'getGroup']);
    Route::get('/groups/{id}/members', [ApiController::class, 'getGroupMembers']);
    Route::get('/user/groups', [ApiController::class, 'getUserGroups']);
    Route::post('/groups/create', [ApiController::class, 'createGroup']);
    Route::post('/groups/join', [ApiController::class, 'joinGroup']);
    Route::delete('/groups/{id}/leave', [ApiController::class, 'leaveGroup']);
    Route::delete('/groups/{id}', [ApiController::class, 'deleteGroup']);

    // ---- Assignments ----
    Route::post('/assignments/create', [ApiController::class, 'createAssignment']);
    Route::put('/assignments/{id}', [ApiController::class, 'updateAssignment']);
    Route::delete('/assignments/{id}', [ApiController::class, 'deleteAssignment']);
    Route::get('/assignments/course/{courseId}', [ApiController::class, 'getCourseAssignments']);
    Route::get('/assignments/{id}', [ApiController::class, 'getAssignment']);

    // ---- Assignment Submissions ----
    Route::post('/assignments/{assignmentId}/submit', [ApiController::class, 'submitAssignment']);
    Route::get('/assignments/{assignmentId}/submissions', [ApiController::class, 'getAssignmentSubmissions']);
    Route::get('/assignments/{assignmentId}/student/{studentId}/submission', [ApiController::class, 'getStudentSubmission']);
    Route::put('/assignments/submission/{id}/grade', [ApiController::class, 'gradeSubmission']);
    Route::delete('/assignments/submission/{id}', [ApiController::class, 'deleteSubmission']);

    // ---- Peer Reviews ----
    Route::post('/peer-reviews/submit', [ApiController::class, 'submitPeerReview']);
    Route::get('/peer-reviews/status/{groupId}', [ApiController::class, 'getPeerReviewStatus']);
    Route::get('/peer-reviews/student/{studentId}', [ApiController::class, 'getReviewsForStudent']);
    Route::get('/peer-reviews/assignment/{assignmentId}/student/{studentId}', [ApiController::class, 'getStudentPeerReviews']);

    // ---- Contribution Scores ----
    Route::post('/contribution/calculate/{studentId}/{assignmentId}', [ApiController::class, 'calculateContributionScore']);
    Route::get('/contribution/score/{studentId}/{assignmentId}', [ApiController::class, 'getStudentScore']);
    Route::get('/contribution/assignment/{assignmentId}', [ApiController::class, 'getAssignmentContributions']);
    Route::get('/contribution/group/{groupId}', [ApiController::class, 'getGroupContributions']);

    // ---- Analytics ----
    Route::get('/analytics/student/{id}', [ApiController::class, 'getStudentAnalytics']);
    Route::get('/analytics/group/{id}', [ApiController::class, 'getGroupAnalytics']);
    Route::get('/analytics/course/{id}', [ApiController::class, 'getCourseAnalytics']);
    Route::get('/analytics/professor/{id}', [ApiController::class, 'getProfessorAnalytics']);

    // ---- AI Evaluation ----
    Route::get('/ai/evaluate/student/{id}', [ApiController::class, 'evaluateStudent']);
    Route::get('/ai/evaluate/group/{id}', [ApiController::class, 'evaluateGroup']);
    Route::get('/ai/evaluate/course/{id}', [ApiController::class, 'evaluateCourse']);
    Route::post('/ai/feedback/student/{id}', [ApiController::class, 'sendFeedback']);

    // ---- Notifications ----
    Route::get('/notifications', [ApiController::class, 'getNotifications']);
    Route::post('/notifications/{id}/read', [ApiController::class, 'markNotificationRead']);
    Route::post('/notifications/read-all', [ApiController::class, 'markAllNotificationsRead']);

    // ---- Reports ----
    Route::post('/reports/generate', [ApiController::class, 'generateReport']);
    Route::get('/reports/download/{type}/{id}', [ApiController::class, 'downloadReport']);

    // ---- Attendance ----
    Route::post('/attendance/mark', [Attendance::class, 'markAttendance']); // Note: Controller mappings remain consistent
    Route::post('/attendance/get', [ApiController::class, 'getStudentAttendance']);
    Route::get('/attendance/course/{courseId}', [ApiController::class, 'getCourseAttendance']);
    Route::get('/attendance/student/{studentId}/course/{courseId}', [ApiController::class, 'getStudentCourseAttendance']);
    Route::put('/attendance/{id}', [ApiController::class, 'updateAttendance']);

    // ---- Working Hours ----
    Route::post('/working-hours/log', [ApiController::class, 'logWorkingHours']);
    Route::get('/working-hours/student/{studentId}', [ApiController::class, 'getStudentWorkingHours']);
    Route::get('/working-hours/group/{groupId}', [ApiController::class, 'getGroupWorkingHours']);
    Route::put('/working-hours/{id}', [ApiController::class, 'updateWorkingHours']);

    // ---- Low Contributors (Slacker Slayer Core) ----
    Route::get('/contributors/low', [ApiController::class, 'getLowContributors']);
    Route::get('/contributors/low/course/{courseId}', [ApiController::class, 'getCourseLowContributors']);
    Route::get('/contributors/low/group/{groupId}', [ApiController::class, 'getGroupLowContributors']);

    // ---- Audit Logs (Admin Only) ----
    Route::get('/audit-logs', [ApiController::class, 'getAuditLogs'])->middleware('admin');
    Route::get('/audit-logs/user/{userId}', [ApiController::class, 'getUserAuditLogs'])->middleware('admin');

    // ---- Admin Routes ----
    Route::get('/admin/users', [ApiController::class, 'getUsers'])->middleware('admin');
    Route::put('/admin/users/{id}/role', [ApiController::class, 'updateUserRole'])->middleware('admin');
    Route::delete('/admin/users/{id}', [ApiController::class, 'deleteUser'])->middleware('admin');
    Route::get('/admin/stats', [ApiController::class, 'getAdminStats'])->middleware('admin');
});