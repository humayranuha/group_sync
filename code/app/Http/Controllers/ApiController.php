<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
    // =========================================================================
    // SECTION 1: AUTHENTICATION & PROFILE
    // =========================================================================

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required']);
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('auth-token')->plainTextToken;
            $this->logActivity('login', $user->id, 'User logged in');

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id, 'first_name' => $user->first_name, 'last_name' => $user->last_name,
                    'email' => $user->email, 'role' => $user->role, 'department' => $user->department ?? null,
                    'profile_picture' => $user->profile_picture ?? null, 'github_connected' => !empty($user->github_token),
                    'github_repo_url' => $user->github_repo_url, 'github_username' => $user->github_username
                ],
                'redirect' => $this->getRedirectUrl($user->role)
            ]);
        }
        $this->logActivity('login_failed', null, 'Failed login attempt for: ' . $request->email);
        return response()->json(['success' => false, 'message' => 'Invalid credentials'], 401);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255', 'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users', 'password' => 'required|min:6|confirmed',
            'role' => 'required|in:student,professor,admin', 'department' => 'nullable|string|max:255'
        ]);
        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        $user = User::create([
            'first_name' => $request->first_name, 'last_name' => $request->last_name,
            'email' => $request->email, 'password' => Hash::make($request->password),
            'role' => $request->role, 'department' => $request->department ?? null, 'is_active' => true
        ]);
        $this->logActivity('register', $user->id, 'User registered');
        return response()->json(['success' => true, 'message' => 'Registration successful', 'user' => $user], 201);
    }

    public function logout(Request $request) {
        $user = $request->user(); $user->currentAccessToken()->delete();
        $this->logActivity('logout', $user->id, 'User logged out');
        return response()->json(['success' => true, 'message' => 'Logged out successfully']);
    }

    public function user(Request $request) { return response()->json(['success' => true, 'user' => $request->user()]); }

    public function updateProfile(Request $request) {
        $user = $request->user();
        if ($request->hasFile('profile_picture')) {
            $user->profile_picture = $request->file('profile_picture')->store('profile_pictures', 'public');
        }
        $user->update($request->only(['first_name', 'last_name', 'department']));
        return response()->json(['success' => true, 'message' => 'Profile updated', 'user' => $user]);
    }

    public function changePassword(Request $request) {
        $request->validate(['current_password' => 'required', 'new_password' => 'required|min:6|confirmed']);
        $user = $request->user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Current password incorrect'], 400);
        }
        $user->update(['password' => Hash::make($request->new_password)]);
        return response()->json(['success' => true, 'message' => 'Password changed successfully']);
    }

    // =========================================================================
    // SECTION 2: GITHUB OAUTH & REPO CONNECTION
    // =========================================================================

    public function githubRedirect() {
        $url = "https://github.com/login/oauth/authorize?client_id=" . env('GITHUB_CLIENT_ID') . "&redirect_uri=" . urlencode(env('GITHUB_REDIRECT_URI')) . "&scope=repo,user:email&state=" . csrf_token();
        return redirect($url);
    }

    

    // 1. Dashboard 403 Error Fix
    public function getStudentDashboard(Request $request) {
        $user = $request->user();
        // Role চেক করার সময় strtolower দিয়ে ক্যাপিটাল/স্মল লেটারের সমস্যা ফিক্স করা হলো
       // ApiController.php এর ভেতরে এই লাইনটি এভাবে পরিবর্তন করুন:
if (trim(strtolower($user->role)) !== 'student') {
    return response()->json(['error' => 'Student access required. Your role is: ' . $user->role], 403);
}
        $c = ContributionScore::where('student_id', $user->id)->first();
        $g = $user->groups()->first();

        return response()->json([
            'success' => true,
            'data' => [
                'total_commits' => $c->commits ?? 0, 'activity_score' => $c->score ?? 0,
                'is_github_connected' => !empty($user->github_token), 'github_repo_url' => $user->github_repo_url,
                'group_name' => $g->name ?? null, 'classification' => $c->classification ?? 'Moderate',
                'score_breakdown' => [
                    'github' => $c ? round($c->commits * 2, 1) : 0, 'attendance' => $c->attendance_score ?? 0,
                    'peer_reviews' => $c->peer_review_score ?? 0, 'working_hours' => $c->working_hours_score ?? 0
                ],
                'group_members' => $g ? $g->members->map(fn($m) => ['name' => $m->first_name, 'contribution_percentage' => ContributionScore::where('student_id', $m->id)->value('score') ?? 0]) : []
            ]
        ]);
    }

    // 2. GitHub Redirect Fix (Token ভিত্তিক API এর জন্য)
    public function githubOAuthRedirect(Request $request) {
        // ইউজারের আইডি এনক্রিপ্ট করে state প্যারামিটারে পাঠিয়ে দিচ্ছি
        $state = encrypt($request->user()->id);
        
        $url = "https://github.com/login/oauth/authorize?client_id=" . env('GITHUB_CLIENT_ID') . "&redirect_uri=" . urlencode(env('GITHUB_REDIRECT_URI')) . "&scope=repo,user:email&state=" . $state;
        
        return response()->json(['success' => true, 'url' => $url]);
    }

    // 3. GitHub Callback Fix
    public function githubCallback(Request $request) {
        try {
            // গিটহাব থেকে ফিরে আসার পর state থেকে ইউজারের আইডি বের করে নিচ্ছি
            $userId = decrypt($request->state);
            $user = User::findOrFail($userId);

            $response = Http::asForm()->post('https://github.com/login/oauth/access_token', [
                'client_id' => env('GITHUB_CLIENT_ID'), 
                'client_secret' => env('GITHUB_CLIENT_SECRET'),
                'code' => $request->code, 
                'state' => $request->state
            ]);
            
            $data = $response->json();
            if (!isset($data['access_token'])) {
                // dashboard.html এ পাঠাচ্ছি (আপনার ফাইল যদি repository-connection.html হয়, তাহলে নাম চেঞ্জ করে দেবেন)
                return redirect('/student/dashboard.html?error=oauth_failed');
            }

            $gitUser = Http::withToken($data['access_token'])->get('https://api.github.com/user')->json();
            
            // স্পেসিফিক ইউজারের ডাটাবেজে টোকেন সেভ করছি
            $user->update([
                'github_token' => $data['access_token'], 
                'github_username' => $gitUser['login'] ?? null
            ]);
            
            return redirect('/student/dashboard.html?github_connected=success');
        } catch (\Exception $e) {
            return redirect('/student/dashboard.html?error=oauth_failed');
        }
    }
    public function connectGitHub(Request $request) {
        $request->validate(['repo_url' => 'required|url']);
        $user = Auth::user();
        if (!$user->github_token) return response()->json(['success' => false, 'message' => 'Authenticate with GitHub first'], 400);

        $parts = explode('/', trim(parse_url($request->repo_url, PHP_URL_PATH), '/'));
        $owner = $parts[0] ?? ''; $name = $parts[1] ?? '';

        $res = Http::withToken($user->github_token)->get("https://api.github.com/repos/{$owner}/{$name}");
        if (!$res->successful()) return response()->json(['success' => false, 'message' => 'Repo not found or private'], 400);

        $user->update(['github_repo_owner' => $owner, 'github_repo_name' => $name, 'github_repo_url' => $request->repo_url, 'github_connected_at' => now()]);
        $this->syncGitHubCommits($user);
        return response()->json(['success' => true, 'message' => 'Repository linked successfully', 'data' => ['owner' => $owner, 'name' => $name]]);
    }

    public function disconnectGitHub() {
        Auth::user()->update(['github_repo_owner' => null, 'github_repo_name' => null, 'github_repo_url' => null, 'github_token' => null, 'total_commits' => 0]);
        return response()->json(['success' => true, 'message' => 'Disconnected successfully']);
    }

    public function getGitHubRepoDetails() {
        $u = Auth::user();
        return response()->json(['success' => true, 'data' => ['repo_url' => $u->github_repo_url, 'owner' => $u->github_repo_owner, 'name' => $u->github_repo_name, 'username' => $u->github_username]]);
    }

    // =========================================================================
    // SECTION 3: THE MASTER CONTRIBUTION ENGINE (FR-03 & FR-05)
    // =========================================================================

    public function syncGitHub() {
        try {
            $user = Auth::user();
            if (!$user->github_token || !$user->github_repo_name) return response()->json(['success' => false, 'message' => 'No repo linked'], 400);

            $this->syncGitHubCommits($user);
            $this->updateContributionScore($user);

            return response()->json(['success' => true, 'message' => 'Sync complete! Scores updated.', 'data' => ['total_commits' => $user->total_commits]]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function syncGitHubCommits(User $user) {
        $url = "https://api.github.com/repos/{$user->github_repo_owner}/{$user->github_repo_name}/stats/contributors";
        $res = Http::withToken($user->github_token)->get($url);
        if ($res->status() === 202) { sleep(2); $res = Http::withToken($user->github_token)->get($url); }
        if (!$res->successful()) return [];

        $commits = 0; $add = 0; $del = 0;
        foreach ($res->json() as $c) {
            if (strtolower(trim($c['author']['login'])) === strtolower(trim($user->github_username))) {
                $commits = $c['total'];
                foreach ($c['weeks'] as $w) { $add += $w['a']; $del += $w['d']; }
                break;
            }
        }
        $group = $user->groups()->first();
        ContributionScore::updateOrCreate(['student_id' => $user->id], [
            'group_id' => $group ? $group->id : null, 'commits' => $commits,
            'lines_added' => $add, 'lines_deleted' => $del, 'calculated_at' => now()
        ]);
        $user->update(['total_commits' => $commits, 'last_github_sync' => now()]);
        return $res->json();
    }

    private function updateContributionScore(User $user) {
        $group = $user->groups()->first();
        $record = ContributionScore::where('student_id', $user->id)->first();
        if (!$record) return;

        // 1. Git Score (40%)
        $gitScore = $this->calculateNormalizedGitScore($user, $group, $record);
        // 2. Peer Review Score (30%) -> converted from 10 point scale to 100%
        $peerAvg = PeerReview::where('reviewee_id', $user->id)->avg('overall_rating') ?? 10;
        $peerScore = min(100, round(($peerAvg * 10), 2));
        // 3. Attendance Score (15%)
        $attScore = $this->calculateAttendanceScore($user->id, $group ? $group->id : null);
        // 4. Hours Score (15%) - Benchmark 12 hrs
        $hrs = WorkingHour::where('user_id', $user->id)->sum('hours') ?? 0;
        $hrsScore = min(100, round(($hrs / 12) * 100, 2));

        // The SRS Formula
        $final = round(($gitScore * 0.40) + ($peerScore * 0.30) + ($attScore * 0.15) + ($hrsScore * 0.15), 2);
        
        $class = $final >= 80 ? 'Excellent' : ($final >= 60 ? 'Good' : ($final < 50 ? 'Slacker / Defaulter' : 'Moderate'));
        $record->update(['score' => $final, 'peer_review_score' => $peerScore, 'attendance_score' => $attScore, 'working_hours_score' => $hrsScore, 'classification' => $class]);
    }

    private function calculateNormalizedGitScore($user, $group, $rec) {
        if (!$group || !$user->github_repo_owner) return min(100, $rec->commits * 2);
        try {
            $data = Http::withToken($user->github_token)->get("https://api.github.com/repos/{$user->github_repo_owner}/{$user->github_repo_name}/stats/contributors")->json();
            $tot = array_sum(array_column($data, 'total'));
            if ($tot == 0) return 0;
            $expected = 1.0 / max(1, $group->members()->count());
            return min(100, round((($rec->commits / $tot) / $expected) * 100, 2));
        } catch (\Exception $e) { return min(100, $rec->commits * 2); }
    }

    private function calculateAttendanceScore($uid, $gid) {
        if (!$gid) return 100;
        $total = Attendance::where('group_id', $gid)->distinct('date')->count('date');
        if ($total == 0) return 100;
        $present = Attendance::where('user_id', $uid)->where('group_id', $gid)->whereIn('status', ['present', 'late'])->count();
        return round(($present / $total) * 100, 2);
    }

    // =========================================================================
    // SECTION 4: STUDENT DASHBOARD & RENAMED HELPERS
    // =========================================================================

   

    private function calculatePeerReviewStats(User $user) {
        $rv = PeerReview::where('reviewee_id', $user->id)->get();
        return ['total' => $rv->count(), 'average' => round($rv->avg('overall_rating') ?? 0, 1), 'reviews' => $rv->take(5)];
    }

    // =========================================================================
    // SECTION 5: COURSES MANAGEMENT (DENSE IMPLEMENTATION)
    // =========================================================================

    public function getCourses() { return response()->json(['success' => true, 'data' => Course::all()]); }
    public function getProfessorCourses($id) { return response()->json(['success' => true, 'data' => Course::where('professor_id', $id)->get()]); }
    public function getStudentCourses($id) { $u = User::find($id); return response()->json(['success' => true, 'data' => $u ? $u->courses : []]); }
    public function createCourse(Request $request) {
        $v = $request->validate(['name' => 'required|string', 'code' => 'required|string', 'professor_id' => 'required|exists:users,id']);
        return response()->json(['success' => true, 'data' => Course::create($v)], 201);
    }
    public function updateCourse(Request $request, $id) { $c = Course::findOrFail($id); $c->update($request->all()); return response()->json(['success' => true, 'data' => $c]); }
    public function enrollStudents(Request $request, $courseId) { Course::findOrFail($courseId)->students()->syncWithoutDetaching($request->student_ids ?? []); return response()->json(['success' => true]); }
    public function enrollViaCode(Request $request) { Course::where('code', $request->code)->firstOrFail()->students()->attach(auth()->id()); return response()->json(['success' => true]); }
    public function getCourseStudents($id) { return response()->json(['success' => true, 'data' => Course::findOrFail($id)->students]); }
    public function enrollStudentsToCourse(Request $r, $cid) { return $this->enrollStudents($r, $cid); }
    public function removeStudentFromCourse($cid, $sid) { Course::findOrFail($cid)->students()->detach($sid); return response()->json(['success' => true]); }
    public function getEnrollableStudents($cid) {
        $enrolled = DB::table('course_student')->where('course_id', $cid)->pluck('user_id');
        return response()->json(['success' => true, 'data' => User::where('role','student')->whereNotIn('id', $enrolled)->get()]);
    }

    // =========================================================================
    // SECTION 6: GROUPS MANAGEMENT (WITH INVITE GENERATOR)
    // =========================================================================

    public function getGroups() { return response()->json(['success' => true, 'data' => Group::with('members')->get()]); }
    public function getGroup($id) { return response()->json(['success' => true, 'data' => Group::with('members')->find($id)]); }
    public function getGroupMembers($id) { return response()->json(['success' => true, 'data' => Group::findOrFail($id)->members]); }
    public function getUserGroups() { return response()->json(['success' => true, 'data' => auth()->user()->groups()->with('members')->get()]); }
    public function createGroup(Request $request) {
        $v = $request->validate(['name' => 'required|string', 'course_id' => 'required|exists:courses,id']);
        $g = Group::create([...$v, 'invite_code' => strtoupper(Str::random(6)), 'created_by' => auth()->id()]);
        $g->members()->attach(auth()->id(), ['role' => 'Leader']);
        return response()->json(['success' => true, 'data' => $g], 201);
    }
    public function joinGroup(Request $request) {
        $g = Group::where('invite_code', $request->invite_code)->firstOrFail();
        $g->members()->syncWithoutDetaching([auth()->id() => ['role' => 'Member']]);
        return response()->json(['success' => true, 'data' => $g]);
    }
    public function leaveGroup($id) { Group::findOrFail($id)->members()->detach(auth()->id()); return response()->json(['success' => true]); }
    public function deleteGroup($id) { Group::destroy($id); return response()->json(['success' => true]); }

    // =========================================================================
    // SECTION 7: ASSIGNMENTS & SUBMISSIONS
    // =========================================================================

    public function createAssignment(Request $r) { return response()->json(['success' => true, 'data' => Assignment::create($r->all())], 201); }
    public function updateAssignment(Request $r, $id) { $a = Assignment::findOrFail($id); $a->update($r->all()); return response()->json(['success' => true, 'data' => $a]); }
    public function deleteAssignment($id) { Assignment::destroy($id); return response()->json(['success' => true]); }
    public function getCourseAssignments($cid) { return response()->json(['success' => true, 'data' => Assignment::where('course_id', $cid)->get()]); }
    public function getAssignment($id) { return response()->json(['success' => true, 'data' => Assignment::findOrFail($id)]); }
    public function submitAssignment(Request $r, $aid) { return response()->json(['success' => true, 'message' => 'Submitted']); }
    public function getAssignmentSubmissions($aid) { return response()->json(['success' => true, 'data' => []]); }
    public function getStudentSubmission($aid, $sid) { return response()->json(['success' => true, 'data' => null]); }
    public function gradeSubmission(Request $r, $id) { return response()->json(['success' => true]); }
    public function deleteSubmission($id) { return response()->json(['success' => true]); }

    // =========================================================================
    // SECTION 8: PEER REVIEWS (TRIGGERS SCORE RE-CALC)
    // =========================================================================

    public function submitPeerReview(Request $request) {
        $v = $request->validate(['group_id' => 'required', 'reviewee_id' => 'required', 'overall_rating' => 'required|numeric|min:1|max:10']);
        PeerReview::create([...$v, 'reviewer_id' => auth()->id(), 'submitted_at' => now()]);
        $target = User::find($request->reviewee_id); if ($target) $this->updateContributionScore($target);
        return response()->json(['success' => true, 'message' => 'Review logged & target student score updated']);
    }
    public function getPeerReviewStatus($gid) { return response()->json(['success' => true, 'data' => PeerReview::where('group_id', $gid)->get()]); }
    public function getReviewsForStudent($sid) { return response()->json(['success' => true, 'data' => PeerReview::where('reviewee_id', $sid)->get()]); }
    public function getStudentPeerReviews($aid, $sid) { return response()->json(['success' => true, 'data' => PeerReview::where('reviewee_id', $sid)->get()]); }

    // =========================================================================
    // SECTION 9: SLACKER SLAYER CORE (LOW CONTRIBUTORS) & SCORES
    // =========================================================================

    public function calculateContributionScore($sid, $aid) { $u = User::findOrFail($sid); $this->updateContributionScore($u); return response()->json(['success' => true]); }
    public function getStudentScore($sid, $aid) { return response()->json(['success' => true, 'data' => ContributionScore::where('student_id', $sid)->first()]); }
    public function getAssignmentContributions($aid) { return response()->json(['success' => true, 'data' => ContributionScore::with('student')->get()]); }
    public function getGroupContributions($gid) { return response()->json(['success' => true, 'data' => ContributionScore::where('group_id', $gid)->with('student')->get()]); }
    
    // THE CROWN JEWEL ROUTE: Finding students below 50% score
    public function getLowContributors() {
        $slackers = ContributionScore::where('score', '<', 50)->with('student')->get();
        return response()->json(['success' => true, 'total_slackers' => $slackers->count(), 'data' => $slackers]);
    }
    public function getCourseLowContributors($cid) { return $this->getLowContributors(); }
    public function getGroupLowContributors($gid) { return response()->json(['success' => true, 'data' => ContributionScore::where('group_id', $gid)->where('score', '<', 50)->with('student')->get()]); }

    // =========================================================================
    // SECTION 10: ATTENDANCE & WORKING HOURS
    // =========================================================================

    public function markAttendance(Request $r) {
        Attendance::create(['user_id' => $r->student_id ?? auth()->id(), 'group_id' => $r->group_id, 'date' => now()->toDateString(), 'status' => $r->status ?? 'present']);
        $u = User::find($r->student_id ?? auth()->id()); if ($u) $this->updateContributionScore($u);
        return response()->json(['success' => true]);
    }
    public function getStudentAttendance() { return response()->json(['success' => true, 'data' => Attendance::where('user_id', auth()->id())->get()]); }
    public function getCourseAttendance($cid) { return response()->json(['success' => true, 'data' => Attendance::all()]); }
    public function getStudentCourseAttendance($sid, $cid) { return response()->json(['success' => true, 'data' => Attendance::where('user_id', $sid)->get()]); }
    public function updateAttendance(Request $r, $id) { Attendance::findOrFail($id)->update($r->all()); return response()->json(['success' => true]); }

    public function logWorkingHours(Request $r) {
        WorkingHour::create(['user_id' => auth()->id(), 'group_id' => $r->group_id, 'hours' => $r->hours, 'date' => now()->toDateString()]);
        $this->updateContributionScore(auth()->user());
        return response()->json(['success' => true, 'message' => 'Hours logged']);
    }
    public function getStudentWorkingHours($sid) { return response()->json(['success' => true, 'data' => WorkingHour::where('user_id', $sid)->get()]); }
    public function getGroupWorkingHours($gid) { return response()->json(['success' => true, 'data' => WorkingHour::where('group_id', $gid)->get()]); }
    public function updateWorkingHours(Request $r, $id) { WorkingHour::findOrFail($id)->update($r->all()); return response()->json(['success' => true]); }

    // =========================================================================
    // SECTION 11: ANALYTICS, AI EVALUATION & MISC SKELETONS
    // =========================================================================

    public function getStudentAnalytics($id) { return response()->json(['success' => true, 'data' => ['score' => 78]]); }
    public function getGroupAnalytics($id) { return response()->json(['success' => true, 'data' => ['health' => 'Good']]); }
    public function getCourseAnalytics($id) { return response()->json(['success' => true, 'data' => ['avg_score' => 74]]); }
    public function getProfessorAnalytics($id) { return response()->json(['success' => true, 'data' => ['active_projects' => 5]]); }
    public function evaluateStudent($id) { return response()->json(['success' => true, 'ai_recommendation' => 'Consistent coder. Assign architecture docs.']); }
    public function evaluateGroup($id) { return response()->json(['success' => true, 'ai_recommendation' => 'Balanced teamwork detected.']); }
    public function evaluateCourse($id) { return response()->json(['success' => true, 'status' => 'Optimal']); }
    public function sendFeedback(Request $r, $id) { return response()->json(['success' => true]); }

    public function getNotifications() { return response()->json(['success' => true, 'data' => Notification::where('user_id', auth()->id())->get()]); }
    public function markNotificationRead($id) { Notification::where('id', $id)->update(['is_read' => true]); return response()->json(['success' => true]); }
    public function markAllNotificationsRead() { Notification::where('user_id', auth()->id())->update(['is_read' => true]); return response()->json(['success' => true]); }

    public function generateReport(Request $r) { return response()->json(['success' => true, 'pdf_url' => url("/api/reports/download/group/1")]); }
    public function downloadReport($type, $id) { return response()->json(['message' => "Mock PDF Export for {$type} #{$id}"]); }

    public function getAuditLogs() { return response()->json(['success' => true, 'data' => AuditLog::latest()->take(20)->get()]); }
    public function getUserAuditLogs($uid) { return response()->json(['success' => true, 'data' => AuditLog::where('user_id', $uid)->get()]); }
    public function getUsers() { return response()->json(['success' => true, 'data' => User::all()]); }
    public function updateUserRole(Request $r, $id) { User::findOrFail($id)->update(['role' => $r->role]); return response()->json(['success' => true]); }
    public function deleteUser($id) { User::destroy($id); return response()->json(['success' => true]); }
    public function getAdminStats() { return response()->json(['success' => true, 'data' => ['total_students' => User::count()]]); }

    protected function logActivity($act, $uid = null, $det = null) { try { AuditLog::create(['user_id' => $uid ?? auth()->id(), 'action' => $act, 'details' => $det]); } catch(\Exception $e){} }
    protected function getRedirectUrl($role) { return $role === 'admin' ? '/admin/dashboard.html' : ($role === 'professor' ? '/professor/dashboard.html' : '/student/dashboard.html'); }
}