<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;  // 👈 এই লাইন যোগ করুন
use App\Models\User;
use App\Models\Course;
use App\Models\Group;
use App\Models\Assignment;
use App\Models\PeerReview;
use App\Models\ContributionScore;
use App\Models\Notification;
use App\Models\AuditLog;
use App\Models\Attendance;
use App\Models\WorkingHour;

class ApiController extends Controller
{
    // ============================================
    // AUTHENTICATION SECTION
    // ============================================

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('auth-token')->plainTextToken;

            $this->logActivity('login', $user->id, 'User logged in');

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'department' => $user->department ?? null,
                    'profile_picture' => $user->profile_picture ?? null
                ],
                'redirect' => $this->getRedirectUrl($user->role)
            ]);
        }

        $this->logActivity('login_failed', null, 'Failed login attempt for email: ' . $request->email);

        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:student,professor,admin',
            'department' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'department' => $request->department ?? null
        ]);

        $this->logActivity('register', $user->id, 'User registered');

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'user' => $user
        ], 201);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();

        $this->logActivity('logout', $user->id, 'User logged out');

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'department' => 'sometimes|string|max:255',
            'profile_picture' => 'sometimes|image|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $user->profile_picture = $path;
        }

        $user->update($request->only(['first_name', 'last_name', 'department']));

        $this->logActivity('profile_update', $user->id, 'Profile updated');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        $this->logActivity('password_change', $user->id, 'Password changed');

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    // ============================================
    // COURSE MANAGEMENT SECTION
    // ============================================

    public function getCourses()
    {
        $courses = Course::with('teacher')->get();
        return response()->json(['success' => true, 'courses' => $courses]);
    }

    public function getProfessorCourses($id)
    {
        $courses = Course::where('teacher_id', $id)->withCount(['students', 'groups'])->get();
        return response()->json(['success' => true, 'courses' => $courses]);
    }

    public function getStudentCourses($id)
    {
        $user = User::findOrFail($id);
        $courses = $user->courses()->with('teacher')->get();
        return response()->json(['success' => true, 'courses' => $courses]);
    }

    public function createCourse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:courses',
            'title' => 'required|string|max:255',
            'semester' => 'required|string',
            'description' => 'nullable|string',
            'enrollment_code' => 'nullable|string|unique:courses'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $course = Course::create([
            'code' => $request->code,
            'title' => $request->title,
            'semester' => $request->semester,
            'description' => $request->description,
            'teacher_id' => $request->user()->id,
            'enrollment_code' => $request->enrollment_code ?? $this->generateEnrollmentCode()
        ]);

        $this->logActivity('create_course', $request->user()->id, 'Created course: ' . $course->code);

        return response()->json([
            'success' => true,
            'message' => 'Course created successfully',
            'course' => $course
        ], 201);
    }

    public function updateCourse(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        if ($course->teacher_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'semester' => 'sometimes|string',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $course->update($request->only(['title', 'semester', 'description']));

        $this->logActivity('update_course', $request->user()->id, 'Updated course: ' . $course->code);

        return response()->json([
            'success' => true,
            'message' => 'Course updated successfully',
            'course' => $course
        ]);
    }

    public function enrollStudents(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);

        if ($course->teacher_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id,role,student'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $course->students()->syncWithoutDetaching($request->student_ids);

        $this->logActivity('enroll_students', $request->user()->id,
            'Enrolled ' . count($request->student_ids) . ' students in course: ' . $course->code);

        return response()->json([
            'success' => true,
            'message' => 'Students enrolled successfully'
        ]);
    }

    public function enrollViaCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enrollment_code' => 'required|string|exists:courses,enrollment_code'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $course = Course::where('enrollment_code', $request->enrollment_code)->first();

        if ($course->students()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Already enrolled'], 400);
        }

        $course->students()->attach($request->user()->id);

        $this->logActivity('self_enroll', $request->user()->id, 'Self-enrolled in course: ' . $course->code);

        return response()->json([
            'success' => true,
            'message' => 'Enrolled successfully',
            'course' => $course
        ]);
    }

    public function getCourseStudents($id)
    {
        $course = Course::with('students')->findOrFail($id);
        return response()->json(['success' => true, 'students' => $course->students]);
    }

    // ============================================
    // GROUP MANAGEMENT SECTION
    // ============================================

    public function getGroups()
    {
        $groups = Group::with(['course', 'members'])->get();
        return response()->json(['success' => true, 'groups' => $groups]);
    }

    public function getGroup($id)
    {
        $group = Group::with(['course', 'members', 'assignments'])->findOrFail($id);
        return response()->json(['success' => true, 'group' => $group]);
    }

    public function getGroupMembers($id)
    {
        $group = Group::findOrFail($id);
        $members = $group->members()->with('contributionScores')->get();
        return response()->json(['success' => true, 'members' => $members]);
    }

    public function getUserGroups(Request $request)
    {
        $groups = $request->user()->groups()->with(['course', 'members'])->get();
        return response()->json(['success' => true, 'groups' => $groups]);
    }

    public function createGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'max_members' => 'nullable|integer|min:2|max:10'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $existingGroup = Group::where('course_id', $request->course_id)
            ->whereHas('members', function($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })->exists();

        if ($existingGroup) {
            return response()->json(['success' => false, 'message' => 'Already in a group'], 400);
        }

        $group = Group::create([
            'name' => $request->name,
            'course_id' => $request->course_id,
            'invitation_code' => $this->generateInvitationCode(),
            'max_members' => $request->max_members ?? 5,
            'created_by' => $request->user()->id,
            'status' => 'active'
        ]);

        $group->members()->attach($request->user()->id, ['joined_at' => now()]);

        $this->logActivity('create_group', $request->user()->id,
            'Created group: ' . $group->name . ' (Code: ' . $group->invitation_code . ')');

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully',
            'group' => $group,
            'invitation_code' => $group->invitation_code
        ], 201);
    }

    public function joinGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invitation_code' => 'required|string',
            'course_id' => 'required|exists:courses,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $group = Group::where('invitation_code', $request->invitation_code)
            ->where('course_id', $request->course_id)
            ->first();

        if (!$group) {
            return response()->json(['success' => false, 'message' => 'Invalid invitation code'], 404);
        }

        if ($group->members()->count() >= $group->max_members) {
            return response()->json(['success' => false, 'message' => 'Group is full'], 400);
        }

        if ($group->status !== 'active') {
            return response()->json(['success' => false, 'message' => 'Group is not active'], 400);
        }

        $existingGroup = Group::where('course_id', $request->course_id)
            ->whereHas('members', function($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })->exists();

        if ($existingGroup) {
            return response()->json(['success' => false, 'message' => 'Already in a group'], 400);
        }

        $group->members()->attach($request->user()->id, ['joined_at' => now()]);

        $this->logActivity('join_group', $request->user()->id, 'Joined group: ' . $group->name);

        return response()->json([
            'success' => true,
            'message' => 'Joined group successfully',
            'group' => $group
        ]);
    }

    public function leaveGroup(Request $request, $id)
    {
        $group = Group::findOrFail($id);

        if (!$group->members()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Not a member'], 400);
        }

        if ($group->created_by == $request->user()->id && $group->members()->count() <= 1) {
            return response()->json(['success' => false, 'message' => 'Cannot leave, you are the only member'], 400);
        }

        $group->members()->detach($request->user()->id);

        $this->logActivity('leave_group', $request->user()->id, 'Left group: ' . $group->name);

        if ($group->created_by == $request->user()->id) {
            $newCreator = $group->members()->first();
            if ($newCreator) {
                $group->created_by = $newCreator->id;
                $group->save();
            }
        }

        return response()->json(['success' => true, 'message' => 'Left group successfully']);
    }

    public function deleteGroup(Request $request, $id)
    {
        $group = Group::findOrFail($id);

        if ($group->created_by !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Only creator can delete'], 403);
        }

        $group->members()->detach();
        $group->delete();

        $this->logActivity('delete_group', $request->user()->id, 'Deleted group: ' . $group->name);

        return response()->json(['success' => true, 'message' => 'Group deleted']);
    }

    // ============================================
    // PEER REVIEW SECTION
    // ============================================

    public function submitPeerReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:groups,id',
            'reviewee_id' => 'required|exists:users,id',
            'assignment_id' => 'required|exists:assignments,id',
            'communication_rating' => 'required|integer|min:1|max:5',
            'reliability_rating' => 'required|integer|min:1|max:5',
            'task_participation_rating' => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $reviewer = $request->user();
        $group = Group::find($request->group_id);

        if (!$group->members()->where('user_id', $reviewer->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Not a member'], 403);
        }

        if (!$group->members()->where('user_id', $request->reviewee_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Reviewee not in group'], 400);
        }

        if ($reviewer->id == $request->reviewee_id) {
            return response()->json(['success' => false, 'message' => 'Cannot review yourself'], 400);
        }

        $existingReview = PeerReview::where('reviewer_id', $reviewer->id)
            ->where('reviewee_id', $request->reviewee_id)
            ->where('assignment_id', $request->assignment_id)
            ->exists();

        if ($existingReview) {
            return response()->json(['success' => false, 'message' => 'Already reviewed this member'], 400);
        }

        $peerReview = PeerReview::create([
            'reviewer_id' => $reviewer->id,
            'reviewee_id' => $request->reviewee_id,
            'group_id' => $request->group_id,
            'assignment_id' => $request->assignment_id,
            'communication_rating' => $request->communication_rating,
            'reliability_rating' => $request->reliability_rating,
            'task_participation_rating' => $request->task_participation_rating,
            'comments' => $request->comments ?? null,
            'submitted_at' => now()
        ]);

        $this->logActivity('submit_peer_review', $reviewer->id,
            'Submitted peer review for student ID: ' . $request->reviewee_id);

        return response()->json([
            'success' => true,
            'message' => 'Peer review submitted successfully',
            'review' => $peerReview
        ], 201);
    }

    public function getPeerReviewStatus(Request $request, $groupId)
    {
        $group = Group::findOrFail($groupId);
        $user = $request->user();

        if (!$group->members()->where('user_id', $user->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Not a member'], 403);
        }

        $members = $group->members()->get();
        $status = [];

        foreach ($members as $member) {
            if ($member->id == $user->id) continue;

            $reviewed = PeerReview::where('reviewer_id', $user->id)
                ->where('reviewee_id', $member->id)
                ->where('group_id', $groupId)
                ->exists();

            $status[] = [
                'member_id' => $member->id,
                'member_name' => $member->first_name . ' ' . $member->last_name,
                'reviewed' => $reviewed,
                'reviewed_at' => $reviewed ? PeerReview::where('reviewer_id', $user->id)
                    ->where('reviewee_id', $member->id)
                    ->first()->submitted_at : null
            ];
        }

        return response()->json([
            'success' => true,
            'status' => $status,
            'total_members' => count($members) - 1,
            'reviewed_count' => collect($status)->where('reviewed', true)->count()
        ]);
    }

    public function getReviewsForStudent($studentId)
    {
        $reviews = PeerReview::with(['reviewer', 'group', 'assignment'])
            ->where('reviewee_id', $studentId)
            ->get();

        $avgCommunication = $reviews->avg('communication_rating') ?? 0;
        $avgReliability = $reviews->avg('reliability_rating') ?? 0;
        $avgParticipation = $reviews->avg('task_participation_rating') ?? 0;
        $averageRating = ($avgCommunication + $avgReliability + $avgParticipation) / 3;

        return response()->json([
            'success' => true,
            'reviews' => $reviews,
            'average_ratings' => [
                'communication' => round($avgCommunication, 2),
                'reliability' => round($avgReliability, 2),
                'task_participation' => round($avgParticipation, 2),
                'overall' => round($averageRating, 2)
            ],
            'count' => $reviews->count()
        ]);
    }

    // ============================================
    // CONTRIBUTION SCORE SECTION
    // ============================================

    public function calculateContributionScore(Request $request, $studentId, $assignmentId)
    {
        $student = User::findOrFail($studentId);
        $assignment = Assignment::findOrFail($assignmentId);

        $group = Group::where('course_id', $assignment->course_id)
            ->whereHas('members', function($query) use ($studentId) {
                $query->where('user_id', $studentId);
            })->first();

        if (!$group) {
            return response()->json(['success' => false, 'message' => 'Student is not in a group'], 400);
        }

        $weightage = json_decode($assignment->weightage, true);

        $gitHubMetrics = $this->getGitHubMetrics($studentId, $assignmentId, $group->id);
        $attendanceScore = $this->getAttendanceScore($studentId, $assignmentId);
        $peerReviews = $this->getPeerReviewScore($studentId, $assignmentId);
        $workingHours = $this->getWorkingHours($studentId, $assignmentId);

        $score = ($gitHubMetrics * $weightage['commits'] / 100) +
                 ($attendanceScore * $weightage['attendance'] / 100) +
                 ($peerReviews * $weightage['peer_reviews'] / 100) +
                 ($workingHours * $weightage['working_hours'] / 100);

        $status = 'normal';
        if ($score < 30) {
            $status = 'critical';
            $this->notifyProfessor($studentId, $assignmentId, $score);
        } elseif ($score < 50) {
            $status = 'warning';
        }

        $contributionScore = ContributionScore::updateOrCreate(
            [
                'student_id' => $studentId,
                'assignment_id' => $assignmentId
            ],
            [
                'group_id' => $group->id,
                'score' => round($score, 2),
                'status' => $status,
                'breakdown' => json_encode([
                    'github_commits' => $gitHubMetrics,
                    'attendance' => $attendanceScore,
                    'peer_reviews' => $peerReviews,
                    'working_hours' => $workingHours,
                    'weightage_used' => $weightage
                ]),
                'calculated_at' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'score' => round($score, 2),
            'status' => $status,
            'breakdown' => $contributionScore->breakdown
        ]);
    }

    public function getStudentScore($studentId, $assignmentId)
    {
        $score = ContributionScore::where('student_id', $studentId)
            ->where('assignment_id', $assignmentId)
            ->first();

        if (!$score) {
            return response()->json(['success' => false, 'message' => 'Score not found'], 404);
        }

        return response()->json(['success' => true, 'score' => $score]);
    }

    // ============================================
    // ANALYTICS SECTION
    // ============================================

    public function getStudentAnalytics($id)
    {
        $scores = ContributionScore::where('student_id', $id)->get();
        $reviews = PeerReview::where('reviewee_id', $id)->get();

        return response()->json([
            'success' => true,
            'total_commits' => $scores->sum('score') > 0 ? rand(50, 200) : 0,
            'total_prs' => rand(5, 30),
            'total_lines_added' => rand(500, 5000),
            'total_lines_deleted' => rand(200, 2000),
            'activity_consistency_score' => $scores->avg('score') ?? 75,
            'team_rank' => rand(1, 5),
            'contribution_percentage' => $scores->avg('score') ?? 35,
            'weekly_data' => [
                ['week' => 1, 'commits' => rand(5, 20)],
                ['week' => 2, 'commits' => rand(5, 20)],
                ['week' => 3, 'commits' => rand(5, 20)],
                ['week' => 4, 'commits' => rand(5, 20)],
            ],
            'daily_activity' => [rand(1, 10), rand(1, 10), rand(1, 10), rand(1, 10), rand(1, 10), rand(1, 10), rand(1, 10)],
            'peer_reviews' => [
                'communication' => $reviews->avg('communication_rating') ?? 0,
                'reliability' => $reviews->avg('reliability_rating') ?? 0,
                'task_participation' => $reviews->avg('task_participation_rating') ?? 0,
            ]
        ]);
    }

    public function getGroupAnalytics($id)
    {
        $group = Group::with(['members', 'members.contributionScores'])->findOrFail($id);
        $members = $group->members;
        $totalScore = $members->sum(function($m) { return $m->contributionScores->avg('score') ?? 0; });
        $avgScore = $members->count() > 0 ? $totalScore / $members->count() : 0;

        return response()->json([
            'success' => true,
            'total_contributions' => $members->sum(function($m) { return $m->contributionScores->count(); }),
            'avg_activity' => round($avgScore, 2),
            'team_performance' => round($avgScore * 1.2, 2),
            'total_commits' => rand(50, 200),
            'weekly_scores' => [65, 70, 75, round($avgScore, 2)],
            'members' => $members->map(function($m) {
                return [
                    'id' => $m->id,
                    'name' => $m->first_name . ' ' . $m->last_name,
                    'contribution_percentage' => round($m->contributionScores->avg('score') ?? 0, 2),
                    'classification' => $this->getClassification($m->contributionScores->avg('score') ?? 0),
                    'commits' => rand(10, 50),
                    'prs' => rand(1, 10),
                    'lines_added' => rand(100, 1000)
                ];
            })
        ]);
    }

    public function evaluateStudent($id)
    {
        $scores = ContributionScore::where('student_id', $id)->get();
        $avgScore = $scores->avg('score') ?? 75;
        $classification = $this->getClassification($avgScore);
        $feedback = $this->getFeedback($classification);

        return response()->json([
            'success' => true,
            'classification' => $classification,
            'participation_score' => round($avgScore, 2),
            'quality_score' => round($avgScore * 0.9, 2),
            'consistency_score' => round($avgScore * 1.1, 2),
            'overall_score' => round($avgScore, 2),
            'feedback' => $feedback,
            'suggestions' => $this->getSuggestions($classification),
            'weekly_labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6', 'Week 7', 'Week 8'],
            'weekly_scores' => [
                rand(60, 80), rand(60, 80), rand(60, 80), rand(60, 80),
                rand(60, 80), rand(60, 80), rand(60, 80), round($avgScore, 2)
            ]
        ]);
    }

    // ============================================
    // NOTIFICATIONS SECTION
    // ============================================

    public function getNotifications(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'notifications' => $notifications
        ]);
    }

    public function markNotificationRead(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);

        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $notification->update([
            'is_read' => true,
            'read_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Notification marked as read']);
    }

    // ============================================
    // GITHUB OAUTH SECTION
    // ============================================

    public function githubRedirect()
    {
        $query = http_build_query([
            'client_id' => env('GITHUB_CLIENT_ID'),
            'redirect_uri' => env('GITHUB_REDIRECT_URI'),
            'scope' => 'repo,user:email',
            'state' => csrf_token(),
        ]);
        return redirect('https://github.com/login/oauth/authorize?' . $query);
    }

    public function githubCallback(Request $request)
    {
        $code = $request->code;
        $clientId = env('GITHUB_CLIENT_ID');
        $clientSecret = env('GITHUB_CLIENT_SECRET');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://github.com/login/oauth/access_token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);

        if (!isset($data['access_token'])) {
            return response()->json(['success' => false, 'message' => 'GitHub authentication failed'], 400);
        }

        $user = $request->user();
        $user->github_token = $data['access_token'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/user');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $data['access_token']]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $userInfo = json_decode(curl_exec($ch), true);
        curl_close($ch);
        $user->github_username = $userInfo['login'] ?? null;
        $user->save();

        $this->logActivity('github_connect', $user->id, 'Connected GitHub account');

        return redirect('/student/dashboard.html');
    }

    // ============================================
    // PRIVATE HELPER METHODS
    // ============================================

    private function getRedirectUrl($role)
    {
        switch($role) {
            case 'professor': return '/professor/dashboard.html';
            case 'admin': return '/admin/dashboard.html';
            default: return '/student/dashboard.html';
        }
    }

    private function generateEnrollmentCode()
    {
        return strtoupper(substr(uniqid(), -6));
    }

    private function generateInvitationCode()
    {
        return strtoupper(substr(uniqid(), -8));
    }

    private function logActivity($action, $userId, $description)
    {
        try {
            AuditLog::create([
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'data' => json_encode(request()->all())
            ]);
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    private function getClassification($score)
    {
        if ($score >= 80) return 'Active';
        if ($score >= 60) return 'Moderate';
        if ($score >= 40) return 'Passive';
        return 'Free Rider';
    }

    private function getFeedback($classification)
    {
        $feedback = [
            'Active' => 'Excellent contribution quality and consistency!',
            'Moderate' => 'Good performance overall. Consider increasing participation.',
            'Passive' => 'Your contribution is below average. Try to engage more.',
            'Free Rider' => 'Your contribution is significantly low. Please increase involvement.'
        ];
        return $feedback[$classification] ?? 'Performance evaluation in progress.';
    }

    private function getSuggestions($classification)
    {
        $suggestions = [
            'Active' => ['Continue your excellent work', 'Help mentor passive group members'],
            'Moderate' => ['Increase code review participation', 'Attend more team meetings'],
            'Passive' => ['Communicate more with your team', 'Increase commit frequency'],
            'Free Rider' => ['Schedule a meeting with your team', 'Start contributing to the repository']
        ];
        return $suggestions[$classification] ?? ['Stay engaged with your team'];
    }

    // ============================================
    // METRICS FETCHING
    // ============================================

    private function getGitHubMetrics($studentId, $assignmentId, $groupId)
    {
        $user = User::find($studentId);
        if (!$user || !$user->github_token) return 0;

        $group = Group::find($groupId);
        if (!$group || !$group->github_repo_url) return 0;

        $path = parse_url($group->github_repo_url, PHP_URL_PATH);
        if (!$path) return 0;
        $parts = explode('/', trim($path, '/'));
        if (count($parts) < 2) return 0;
        $owner = $parts[0];
        $repo = $parts[1];

        try {
            $response = Http::withToken($user->github_token)
                ->get("https://api.github.com/repos/{$owner}/{$repo}/commits", [
                    'per_page' => 100,
                    'since' => now()->subDays(30)->toIso8601String()
                ]);

            if ($response->successful()) {
                $commits = $response->json();
                $commitCount = count($commits);
                return min(100, ($commitCount / 50) * 100);
            }
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    // 👇 নাম পরিবর্তন করা হয়েছে (পুরনো getAttendance থেকে getAttendanceScore)
    private function getAttendanceScore($studentId, $assignmentId)
    {
        $assignment = Assignment::find($assignmentId);
        if (!$assignment) return 0;

        $courseId = $assignment->course_id;

        $totalDays = Attendance::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->count();

        if ($totalDays == 0) return 0;

        $presentDays = Attendance::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('present', true)
            ->count();

        return round(($presentDays / $totalDays) * 100);
    }

    private function getPeerReviewScore($studentId, $assignmentId)
    {
        $reviews = PeerReview::where('reviewee_id', $studentId)
            ->where('assignment_id', $assignmentId)
            ->get();

        if ($reviews->isEmpty()) return 0;

        $avg = ($reviews->avg('communication_rating') +
                $reviews->avg('reliability_rating') +
                $reviews->avg('task_participation_rating')) / 3;

        return ($avg / 5) * 100;
    }

    private function getWorkingHours($studentId, $assignmentId)
    {
        $totalHours = WorkingHour::where('student_id', $studentId)
            ->where('assignment_id', $assignmentId)
            ->sum('hours');

        return min(100, round(($totalHours / 40) * 100));
    }

    private function notifyProfessor($studentId, $assignmentId, $score)
    {
        try {
            $student = User::find($studentId);
            $assignment = Assignment::find($assignmentId);
            $professor = $assignment->course->teacher;

            if ($professor) {
                Notification::create([
                    'user_id' => $professor->id,
                    'type' => 'low_score_alert',
                    'title' => 'Low Contribution Score Alert',
                    'message' => "Student {$student->first_name} {$student->last_name} has a contribution score of {$score}% for assignment {$assignment->title}.",
                    'data' => json_encode(['student_id' => $studentId, 'assignment_id' => $assignmentId, 'score' => $score])
                ]);
            }
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    // ============================================
    // REPORT GENERATION
    // ============================================

    public function generateReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:student,group,course',
            'id' => 'required|integer',
            'format' => 'sometimes|in:html,csv,txt'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $format = $request->format ?? 'html';
        $type = $request->type;
        $id = $request->id;

        switch ($type) {
            case 'student':
                return $this->generateStudentReport($id, $format);
            case 'group':
                return $this->generateGroupReport($id, $format);
            case 'course':
                return $this->generateCourseReport($id, $format);
            default:
                return response()->json(['success' => false, 'message' => 'Invalid report type'], 400);
        }
    }

    private function generateStudentReport($studentId, $format = 'html')
    {
        $student = User::with(['contributionScores', 'receivedPeerReviews'])->findOrFail($studentId);
        $scores = $student->contributionScores;
        $avgScore = $scores->avg('score') ?? 0;
        $classification = $this->getClassification($avgScore);

        $data = [
            'student' => $student,
            'scores' => $scores,
            'avg_score' => round($avgScore, 2),
            'classification' => $classification,
            'total_commits' => $scores->sum('score') > 0 ? rand(50, 200) : 0,
            'generated_at' => now()->toDateTimeString()
        ];

        if ($format === 'csv') {
            return $this->generateStudentCSV($data);
        }
        if ($format === 'txt') {
            return $this->generateStudentTXT($data);
        }
        return $this->generateStudentHTML($data);
    }

    private function generateStudentHTML($data)
    {
        $student = $data['student'];
        $scores = $data['scores'];
        $avgScore = $data['avg_score'];
        $classification = $data['classification'];
        $totalCommits = $data['total_commits'];
        $generatedAt = $data['generated_at'];

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head><title>Student Report</title>
<style>
body { font-family: Arial; padding: 40px; }
.header { text-align: center; border-bottom: 2px solid #3AAFA9; padding-bottom: 20px; }
.score { font-size: 24px; color: #3AAFA9; font-weight: bold; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f2f2f2; }
.footer { margin-top: 40px; text-align: center; color: #999; font-size: 12px; }
</style>
</head>
<body>
<div class="header"><h1>📊 Student Performance Report</h1><p>Generated: {$generatedAt}</p></div>
<h2>{$student->first_name} {$student->last_name}</h2>
<p><strong>Email:</strong> {$student->email}</p>
<p><strong>Department:</strong> {$student->department}</p>
<p><strong>Role:</strong> {$student->role}</p>
<hr>
<h3>Performance Summary</h3>
<p><strong>Average Score:</strong> <span class="score">{$avgScore}%</span></p>
<p><strong>Classification:</strong> {$classification}</p>
<p><strong>Total Commits:</strong> {$totalCommits}</p>
<p><strong>Peer Reviews:</strong> {$student->receivedPeerReviews->count()}</p>
HTML;

        if ($scores->count() > 0) {
            $html .= '<h3>Score Breakdown</h3><table><tr><th>Assignment</th><th>Score</th><th>Status</th></tr>';
            foreach ($scores as $score) {
                $html .= "<tr><td>{$score->assignment->title}</td><td>{$score->score}%</td><td>{$score->status}</td></tr>";
            }
            $html .= '</table>';
        }

        $html .= '<div class="footer">Generated by GroupSync — AI-Powered Academic Collaboration Analytics</div></body></html>';
        return response($html)->header('Content-Type', 'text/html');
    }

    private function generateStudentCSV($data)
    {
        $student = $data['student'];
        $scores = $data['scores'];
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=student_report_{$student->id}.csv"];
        $callback = function() use ($student, $scores) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Student Performance Report']);
            fputcsv($file, ['Name', $student->first_name . ' ' . $student->last_name]);
            fputcsv($file, ['Email', $student->email]);
            fputcsv($file, ['Assignment', 'Score', 'Status']);
            foreach ($scores as $score) {
                fputcsv($file, [$score->assignment->title, $score->score . '%', $score->status]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function generateStudentTXT($data)
    {
        $student = $data['student'];
        $scores = $data['scores'];
        $avgScore = $data['avg_score'];
        $classification = $data['classification'];
        $txt = "============================================\n";
        $txt .= "     STUDENT PERFORMANCE REPORT\n";
        $txt .= "============================================\n\n";
        $txt .= "Name: {$student->first_name} {$student->last_name}\n";
        $txt .= "Email: {$student->email}\n";
        $txt .= "Classification: {$classification}\n";
        $txt .= "Average Score: {$avgScore}%\n\n";
        $txt .= "--------------------------------------------\n";
        $txt .= "     SCORE BREAKDOWN\n";
        $txt .= "--------------------------------------------\n";
        foreach ($scores as $score) {
            $txt .= "Assignment: {$score->assignment->title}\n";
            $txt .= "Score: {$score->score}%\n";
            $txt .= "Status: {$score->status}\n\n";
        }
        $txt .= "--------------------------------------------\n";
        $txt .= "Generated by GroupSync\n";
        return response($txt)->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=student_report_{$student->id}.txt");
    }

    private function generateGroupReport($groupId, $format = 'html')
    {
        $group = Group::with(['members', 'members.contributionScores'])->findOrFail($groupId);
        $members = $group->members;
        $avgScore = round($members->avg(function($m) { return $m->contributionScores->avg('score') ?? 0; }), 2);

        $data = [
            'group' => $group,
            'members' => $members,
            'total_members' => $members->count(),
            'avg_score' => $avgScore,
            'generated_at' => now()->toDateTimeString()
        ];

        if ($format === 'csv') {
            return $this->generateGroupCSV($data);
        }
        if ($format === 'txt') {
            return $this->generateGroupTXT($data);
        }
        return $this->generateGroupHTML($data);
    }

    private function generateGroupHTML($data)
    {
        $group = $data['group'];
        $members = $data['members'];
        $avgScore = $data['avg_score'];
        $generatedAt = $data['generated_at'];

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head><title>Group Report</title>
<style>
body { font-family: Arial; padding: 40px; }
.header { text-align: center; border-bottom: 2px solid #3AAFA9; padding-bottom: 20px; }
.score { font-size: 24px; color: #3AAFA9; font-weight: bold; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f2f2f2; }
.footer { margin-top: 40px; text-align: center; color: #999; font-size: 12px; }
</style>
</head>
<body>
<div class="header"><h1>👥 Group Performance Report</h1><p>Generated: {$generatedAt}</p></div>
<h2>{$group->name}</h2>
<p><strong>Course:</strong> {$group->course->title}</p>
<p><strong>Total Members:</strong> {$data['total_members']}</p>
<p class="score">Average Score: {$avgScore}%</p>
<h3>Member Details</h3>
<table><tr><th>Name</th><th>Role</th><th>Contribution %</th></tr>
HTML;
        foreach ($members as $member) {
            $html .= "<tr><td>{$member->first_name} {$member->last_name}</td><td>Student</td><td>" . round($member->contributionScores->avg('score') ?? 0, 2) . "%</td></tr>";
        }
        $html .= '</table><div class="footer">Generated by GroupSync — AI-Powered Academic Collaboration Analytics</div></body></html>';
        return response($html)->header('Content-Type', 'text/html');
    }

    private function generateGroupCSV($data)
    {
        $group = $data['group'];
        $members = $data['members'];
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=group_report_{$group->id}.csv"];
        $callback = function() use ($group, $members) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Group Performance Report']);
            fputcsv($file, ['Group', $group->name]);
            fputcsv($file, ['Name', 'Contribution %']);
            foreach ($members as $member) {
                fputcsv($file, [$member->first_name . ' ' . $member->last_name, round($member->contributionScores->avg('score') ?? 0, 2) . '%']);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function generateGroupTXT($data)
    {
        $group = $data['group'];
        $members = $data['members'];
        $txt = "============================================\n";
        $txt .= "     GROUP PERFORMANCE REPORT\n";
        $txt .= "============================================\n\n";
        $txt .= "Group: {$group->name}\n";
        $txt .= "Course: {$group->course->title}\n";
        $txt .= "Total Members: {$data['total_members']}\n";
        $txt .= "Average Score: {$data['avg_score']}%\n\n";
        $txt .= "--------------------------------------------\n";
        $txt .= "     MEMBER DETAILS\n";
        $txt .= "--------------------------------------------\n";
        foreach ($members as $member) {
            $txt .= "Name: {$member->first_name} {$member->last_name}\n";
            $txt .= "Contribution: " . round($member->contributionScores->avg('score') ?? 0, 2) . "%\n\n";
        }
        $txt .= "--------------------------------------------\n";
        $txt .= "Generated by GroupSync\n";
        return response($txt)->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=group_report_{$group->id}.txt");
    }

    private function generateCourseReport($courseId, $format = 'html')
    {
        $course = Course::with(['students', 'groups'])->findOrFail($courseId);
        $students = $course->students;

        $data = [
            'course' => $course,
            'students' => $students,
            'total_students' => $students->count(),
            'total_groups' => $course->groups->count(),
            'generated_at' => now()->toDateTimeString()
        ];

        if ($format === 'csv') {
            return $this->generateCourseCSV($data);
        }
        if ($format === 'txt') {
            return $this->generateCourseTXT($data);
        }
        return $this->generateCourseHTML($data);
    }

    private function generateCourseHTML($data)
    {
        $course = $data['course'];
        $students = $data['students'];
        $generatedAt = $data['generated_at'];

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head><title>Course Report</title>
<style>
body { font-family: Arial; padding: 40px; }
.header { text-align: center; border-bottom: 2px solid #3AAFA9; padding-bottom: 20px; }
table { width: 100%; border-collapse: collapse; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f2f2f2; }
.footer { margin-top: 40px; text-align: center; color: #999; font-size: 12px; }
</style>
</head>
<body>
<div class="header"><h1>📚 Course Report</h1><p>Generated: {$generatedAt}</p></div>
<h2>{$course->code} - {$course->title}</h2>
<p><strong>Semester:</strong> {$course->semester}</p>
<p><strong>Total Students:</strong> {$data['total_students']}</p>
<p><strong>Total Groups:</strong> {$data['total_groups']}</p>
<h3>Students List</h3>
<table><tr><th>Name</th><th>Email</th><th>Group</th></tr>
HTML;
        foreach ($students as $student) {
            $group = $student->groups()->first();
            $html .= "<tr><td>{$student->first_name} {$student->last_name}</td><td>{$student->email}</td><td>" . ($group->name ?? 'Not Assigned') . "</td></tr>";
        }
        $html .= '</table><div class="footer">Generated by GroupSync — AI-Powered Academic Collaboration Analytics</div></body></html>';
        return response($html)->header('Content-Type', 'text/html');
    }

    private function generateCourseCSV($data)
    {
        $course = $data['course'];
        $students = $data['students'];
        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=course_report_{$course->id}.csv"];
        $callback = function() use ($course, $students) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Course Performance Report']);
            fputcsv($file, ['Course', $course->code . ' - ' . $course->title]);
            fputcsv($file, ['Semester', $course->semester]);
            fputcsv($file, ['Name', 'Email', 'Group']);
            foreach ($students as $student) {
                $group = $student->groups()->first();
                fputcsv($file, [$student->first_name . ' ' . $student->last_name, $student->email, $group->name ?? 'Not Assigned']);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function generateCourseTXT($data)
    {
        $course = $data['course'];
        $students = $data['students'];
        $txt = "============================================\n";
        $txt .= "     COURSE PERFORMANCE REPORT\n";
        $txt .= "============================================\n\n";
        $txt .= "Course: {$course->code} - {$course->title}\n";
        $txt .= "Semester: {$course->semester}\n";
        $txt .= "Total Students: {$data['total_students']}\n\n";
        $txt .= "--------------------------------------------\n";
        $txt .= "     STUDENT LIST\n";
        $txt .= "--------------------------------------------\n";
        foreach ($students as $student) {
            $group = $student->groups()->first();
            $txt .= "Name: {$student->first_name} {$student->last_name}\n";
            $txt .= "Email: {$student->email}\n";
            $txt .= "Group: " . ($group->name ?? 'Not Assigned') . "\n\n";
        }
        $txt .= "--------------------------------------------\n";
        $txt .= "Generated by GroupSync\n";
        return response($txt)->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename=course_report_{$course->id}.txt");
    }

    // ============================================
    // ATTENDANCE MANAGEMENT
    // ============================================

    public function markAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id,role,student',
            'course_id' => 'required|exists:courses,id',
            'date' => 'required|date',
            'present' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $attendance = Attendance::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'course_id' => $request->course_id,
                'date' => $request->date
            ],
            [
                'present' => $request->present
            ]
        );

        $this->logActivity('mark_attendance', $request->user()->id,
            "Marked attendance for student {$request->student_id} on {$request->date}");

        return response()->json([
            'success' => true,
            'message' => 'Attendance marked successfully',
            'attendance' => $attendance
        ]);
    }

    public function getStudentAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $query = Attendance::where('student_id', $request->student_id)
            ->where('course_id', $request->course_id);

        if ($request->start_date) {
            $query->where('date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->where('date', '<=', $request->end_date);
        }

        $attendance = $query->orderBy('date', 'desc')->get();

        $totalDays = $attendance->count();
        $presentDays = $attendance->where('present', true)->count();
        $attendanceRate = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'attendance' => $attendance,
            'summary' => [
                'total_days' => $totalDays,
                'present_days' => $presentDays,
                'absent_days' => $totalDays - $presentDays,
                'attendance_rate' => $attendanceRate
            ]
        ]);
    }

    public function getCourseAttendance($courseId)
    {
        $students = User::where('role', 'student')->get();
        $attendanceData = [];

        foreach ($students as $student) {
            $attendance = Attendance::where('student_id', $student->id)
                ->where('course_id', $courseId)
                ->get();

            $total = $attendance->count();
            $present = $attendance->where('present', true)->count();
            $rate = $total > 0 ? round(($present / $total) * 100, 2) : 0;

            $attendanceData[] = [
                'student_id' => $student->id,
                'student_name' => $student->first_name . ' ' . $student->last_name,
                'total_days' => $total,
                'present_days' => $present,
                'absent_days' => $total - $present,
                'attendance_rate' => $rate
            ];
        }

        return response()->json([
            'success' => true,
            'course_id' => $courseId,
            'attendance' => $attendanceData
        ]);
    }
}