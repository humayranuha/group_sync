<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
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
                    'profile_picture' => $user->profile_picture ?? null,
                    'github_connected' => $user->hasGithubConnected(),
                    'github_repo_url' => $user->github_repo_url
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
            'department' => $request->department ?? null,
            'is_active' => true
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
    // STUDENT DASHBOARD
    // ============================================

    public function getStudentDashboard(Request $request)
    {
        $user = $request->user();

        if (!$user->isStudent()) {
            return response()->json([
                'error' => 'Unauthorized. Student access required.'
            ], 403);
        }

        // Get or calculate contribution data
        $contributionData = $this->getStudentContributions($user);

        // Get peer reviews
        $peerReviews = $this->getStudentPeerReviews($user);

        // Get group info
        $groupInfo = $this->getStudentGroupInfo($user);

        // Get weekly data
        $weeklyData = $this->getWeeklyData($user->id);

        // Get daily activity
        $dailyActivity = $this->getDailyActivity($user->id);

        // Get score breakdown
        $scoreBreakdown = $this->getScoreBreakdown($user->id);

        // Get team rankings
        $teamRankings = $this->getTeamRankings($user->id);

        // Get AI feedback
        $aiFeedback = $this->getAIFeedback($user, $contributionData);

        return response()->json([
            'success' => true,
            'data' => [
                'total_commits' => $contributionData['commits'] ?? 0,
                'total_prs' => $contributionData['pull_requests'] ?? 0,
                'peer_reviews' => $peerReviews['total'] ?? 0,
                'activity_consistency_score' => $contributionData['score'] ?? 0,
                'activity_score' => $contributionData['score'] ?? 0,
                'team_rank' => $groupInfo['rank'] ?? 'N/A',
                'is_github_connected' => $user->hasGithubConnected(),
                'github_repo_url' => $user->github_repo_url,
                'weekly_data' => $weeklyData,
                'daily_activity' => $dailyActivity,
                'score_breakdown' => $scoreBreakdown,
                'group_name' => $groupInfo['name'] ?? null,
                'group_members' => $groupInfo['members'] ?? [],
                'team_ranking' => $teamRankings,
                'peer_breakdown' => $peerReviews['reviews'] ?? [],
                'classification' => $contributionData['classification'] ?? 'Moderate',
                'feedback' => $aiFeedback['feedback'] ?? '',
                'suggestions' => $aiFeedback['suggestions'] ?? [],
                'participation_score' => $aiFeedback['participation_score'] ?? 0,
                'quality_score' => $aiFeedback['quality_score'] ?? 0,
                'consistency_score' => $aiFeedback['consistency_score'] ?? 0,
                'overall_score' => $aiFeedback['overall_score'] ?? 0,
            ]
        ]);
    }

    // ============================================
    // PRIVATE HELPER METHODS FOR DASHBOARD
    // ============================================

    private function getStudentContributions($user)
    {
        // Try to get from database
        $contribution = ContributionScore::where('student_id', $user->id)
            ->latest('calculated_at')
            ->first();

        if ($contribution) {
            return [
                'commits' => $contribution->commits ?? 0,
                'pull_requests' => $contribution->pull_requests ?? 0,
                'forks' => $contribution->forks ?? 0,
                'lines_added' => $contribution->lines_added ?? 0,
                'lines_deleted' => $contribution->lines_deleted ?? 0,
                'score' => $contribution->score ?? 0,
                'classification' => $contribution->classification ?? 'Moderate',
                'peer_review_score' => $contribution->peer_review_score ?? 0,
                'attendance_score' => $contribution->attendance_score ?? 0,
                'working_hours_score' => $contribution->working_hours_score ?? 0,
            ];
        }

        // If no contribution found, try to calculate from GitHub
        if ($user->hasGithubConnected()) {
            $githubData = $this->fetchGitHubData($user);
            if ($githubData) {
                // Save to database
                $contribution = ContributionScore::create([
                    'student_id' => $user->id,
                    'commits' => $githubData['commits'] ?? 0,
                    'pull_requests' => $githubData['pull_requests'] ?? 0,
                    'forks' => $githubData['forks'] ?? 0,
                    'lines_added' => $githubData['lines_added'] ?? 0,
                    'lines_deleted' => $githubData['lines_deleted'] ?? 0,
                    'score' => $githubData['score'] ?? 0,
                    'calculated_at' => now()
                ]);

                return [
                    'commits' => $contribution->commits ?? 0,
                    'pull_requests' => $contribution->pull_requests ?? 0,
                    'forks' => $contribution->forks ?? 0,
                    'lines_added' => $contribution->lines_added ?? 0,
                    'lines_deleted' => $contribution->lines_deleted ?? 0,
                    'score' => $contribution->score ?? 0,
                    'classification' => $contribution->classification ?? 'Moderate',
                    'peer_review_score' => 0,
                    'attendance_score' => 0,
                    'working_hours_score' => 0,
                ];
            }
        }

        // Return default data
        return [
            'commits' => 0,
            'pull_requests' => 0,
            'forks' => 0,
            'lines_added' => 0,
            'lines_deleted' => 0,
            'score' => 0,
            'classification' => 'Moderate',
            'peer_review_score' => 0,
            'attendance_score' => 0,
            'working_hours_score' => 0,
        ];
    }

    private function fetchGitHubData($user)
    {
        if (!$user->hasGithubConnected()) {
            return null;
        }

        try {
            $repoPath = $user->github_repo_path;
            if (!$repoPath) {
                return null;
            }

            $token = $user->github_token;
            if (!$token) {
                return null;
            }

            // Fetch commits
            $commitsResponse = Http::withHeaders([
                'Authorization' => "token {$token}",
                'Accept' => 'application/vnd.github.v3+json'
            ])->get("https://api.github.com/repos/{$repoPath}/commits", [
                'author' => $user->github_username,
                'per_page' => 100
            ]);

            $commits = $commitsResponse->successful() ? count($commitsResponse->json()) : 0;

            // Fetch pull requests
            $prResponse = Http::withHeaders([
                'Authorization' => "token {$token}",
                'Accept' => 'application/vnd.github.v3+json'
            ])->get("https://api.github.com/repos/{$repoPath}/pulls", [
                'state' => 'all',
                'per_page' => 100
            ]);

            $pullRequests = $prResponse->successful() ? count($prResponse->json()) : 0;

            // Fetch forks
            $forksResponse = Http::withHeaders([
                'Authorization' => "token {$token}",
                'Accept' => 'application/vnd.github.v3+json'
            ])->get("https://api.github.com/repos/{$repoPath}/forks", [
                'per_page' => 100
            ]);

            $forks = $forksResponse->successful() ? count($forksResponse->json()) : 0;

            // Calculate score
            $score = $this->calculateScore($commits, $pullRequests, $forks);

            return [
                'commits' => $commits,
                'pull_requests' => $pullRequests,
                'forks' => $forks,
                'lines_added' => 0,
                'lines_deleted' => 0,
                'score' => $score,
            ];
        } catch (\Exception $e) {
            \Log::error('GitHub API Error: ' . $e->getMessage());
            return null;
        }
    }

    private function calculateScore($commits, $prs, $forks)
    {
        $score = 0;
        $score += min($commits * 2, 50);
        $score += min($prs * 5, 25);
        $score += min($forks * 3, 15);
        return min(round($score), 100);
    }

    private function getStudentPeerReviews($user)
    {
        $reviews = PeerReview::where('reviewee_id', $user->id)
            ->whereNotNull('submitted_at')
            ->get();

        $total = $reviews->count();
        $average = $total > 0 ? $reviews->avg('overall_rating') : 0;

        return [
            'total' => $total,
            'average' => round($average, 1),
            'reviews' => $reviews->take(5)->map(function($review) {
                return [
                    'reviewer' => $review->reviewer ? $review->reviewer->full_name : 'Anonymous',
                    'rating' => $review->overall_rating ?? $review->average_rating,
                    'comment' => $review->comments,
                    'submitted_at' => $review->submitted_at
                ];
            })
        ];
    }

    private function getStudentGroupInfo($user)
    {
        $group = $user->groups()->first();

        if (!$group) {
            return [
                'name' => null,
                'rank' => 'N/A',
                'members' => []
            ];
        }

        $members = $group->members()->get();

        // Calculate rank
        $rank = $this->calculateTeamRank($user->id, $group->id);

        return [
            'name' => $group->name,
            'rank' => $rank,
            'members' => $members->map(function($member) use ($group) {
                $score = ContributionScore::where('student_id', $member->id)
                    ->where('group_id', $group->id)
                    ->latest('calculated_at')
                    ->first();

                return [
                    'id' => $member->id,
                    'name' => $member->full_name . ($member->id == auth()->id() ? ' (You)' : ''),
                    'role' => $member->pivot->role ?? 'member',
                    'commits' => $score->commits ?? 0,
                    'contribution_percentage' => $score->score ?? 0,
                    'classification' => $score->classification ?? 'Moderate'
                ];
            })
        ];
    }

    private function calculateTeamRank($userId, $groupId)
    {
        $group = Group::find($groupId);
        if (!$group) {
            return 'N/A';
        }

        $allGroups = Group::where('course_id', $group->course_id)->get();
        $rankings = [];

        foreach ($allGroups as $g) {
            $avgScore = ContributionScore::where('group_id', $g->id)->avg('score') ?? 0;
            $rankings[] = [
                'group_id' => $g->id,
                'score' => $avgScore
            ];
        }

        usort($rankings, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        foreach ($rankings as $index => $rank) {
            if ($rank['group_id'] == $groupId) {
                return $index + 1;
            }
        }

        return 'N/A';
    }

    private function getWeeklyData($userId)
    {
        $data = [];
        $scores = ContributionScore::where('student_id', $userId)
            ->latest('calculated_at')
            ->take(8)
            ->get()
            ->reverse();

        $week = 1;
        foreach ($scores as $score) {
            $data[] = [
                'week' => $week++,
                'commits' => $score->commits ?? 0
            ];
        }

        // If no data, return default
        if (empty($data)) {
            for ($i = 1; $i <= 8; $i++) {
                $data[] = ['week' => $i, 'commits' => 0];
            }
        }

        return $data;
    }

    private function getDailyActivity($userId)
    {
        $activity = [];
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        $scores = ContributionScore::where('student_id', $userId)
            ->where('calculated_at', '>=', now()->subDays(7))
            ->get();

        if ($scores->count() > 0) {
            foreach ($days as $index => $day) {
                $dayScore = $scores->filter(function($s) use ($index) {
                    return $s->calculated_at->dayOfWeek == ($index + 1);
                })->first();

                $activity[] = $dayScore ? round($dayScore->score / 10, 0) : 0;
            }
        } else {
            $activity = [0, 0, 0, 0, 0, 0, 0];
        }

        return $activity;
    }

    private function getScoreBreakdown($userId)
    {
        $score = ContributionScore::where('student_id', $userId)
            ->latest('calculated_at')
            ->first();

        if ($score) {
            return [
                'github' => $score->score ?? 0,
                'attendance' => $score->attendance_score ?? 0,
                'peer_reviews' => $score->peer_review_score ?? 0,
                'working_hours' => $score->working_hours_score ?? 0
            ];
        }

        return [
            'github' => 0,
            'attendance' => 0,
            'peer_reviews' => 0,
            'working_hours' => 0
        ];
    }

    private function getTeamRankings($userId)
    {
        $user = User::find($userId);
        $group = $user->groups()->first();

        if (!$group) {
            return [];
        }

        $allGroups = Group::where('course_id', $group->course_id)->get();
        $rankings = [];

        foreach ($allGroups as $g) {
            $avgScore = ContributionScore::where('group_id', $g->id)->avg('score') ?? 0;

            $rankings[] = [
                'team' => $g->name,
                'rank' => 0,
                'score' => round($avgScore, 2)
            ];
        }

        usort($rankings, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        foreach ($rankings as $index => &$rank) {
            $rank['rank'] = $index + 1;
        }

        return $rankings;
    }

    private function getAIFeedback($user, $contributionData)
    {
        $score = $contributionData['score'] ?? 0;
        $classification = $contributionData['classification'] ?? 'Moderate';

        $feedback = '';
        $suggestions = [];

        if ($score >= 80) {
            $feedback = "Excellent contribution quality and consistency! You're demonstrating strong collaboration skills and delivering high-quality work. Your activity pattern shows regular engagement with the repository. You've consistently met your team's expectations and shown initiative in completing tasks.";
            $suggestions = [
                "Continue mentoring other team members",
                "Share your best practices with the team",
                "Take on more challenging tasks"
            ];
        } elseif ($score >= 60) {
            $feedback = "Good performance! You're consistently contributing to the project. Keep up the momentum. Your work quality is good and you're showing reliability in completing tasks.";
            $suggestions = [
                "Increase code review participation by reviewing teammates' PRs regularly",
                "Help mentor passive group members to improve overall team performance",
                "Document your code more thoroughly for better knowledge transfer"
            ];
        } elseif ($score >= 40) {
            $feedback = "You're showing moderate participation. There's room for improvement in your contribution levels. Try to engage more with the repository and your team members.";
            $suggestions = [
                "Set daily contribution goals",
                "Participate more in code reviews",
                "Communicate more with team members",
                "Try to make at least one commit per day"
            ];
        } else {
            $feedback = "Your contribution levels are below expectations. We recommend taking immediate action to improve participation. Your team may be relying on you for specific tasks.";
            $suggestions = [
                "Reach out to your team members for support",
                "Set a regular schedule for contributions",
                "Start with small, manageable tasks",
                "Schedule a meeting with your team to discuss expectations"
            ];
        }

        return [
            'feedback' => $feedback,
            'suggestions' => $suggestions,
            'classification' => $classification,
            'participation_score' => $score,
            'quality_score' => min($score + 10, 100),
            'consistency_score' => max($score - 5, 0),
            'overall_score' => $score
        ];
    }

    // ============================================
    // GITHUB OAUTH SECTION (সম্পূর্ণ আপডেটেড)
    // ============================================

    public function githubRedirect()
    {
        $clientId = env('GITHUB_CLIENT_ID');
        $redirectUri = env('GITHUB_REDIRECT_URI', url('/api/auth/github/callback'));
        $scope = 'repo,user:email';
        $state = csrf_token();
        
        $url = "https://github.com/login/oauth/authorize?client_id={$clientId}&redirect_uri=" . urlencode($redirectUri) . "&scope={$scope}&state={$state}";
        
        return redirect($url);
    }

    public function githubCallback(Request $request)
    {
        $code = $request->code;
        $state = $request->state;
        
        $clientId = env('GITHUB_CLIENT_ID');
        $clientSecret = env('GITHUB_CLIENT_SECRET');

        // Exchange code for access token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://github.com/login/oauth/access_token');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code,
            'state' => $state
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);

        if (!isset($data['access_token'])) {
            return redirect('/student/dashboard.html?error=github_auth_failed');
        }

        $accessToken = $data['access_token'];

        // Get user data from GitHub
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.github.com/user');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/vnd.github.v3+json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $userInfo = json_decode(curl_exec($ch), true);
        curl_close($ch);

        // Save GitHub token to user
        $user = $request->user();
        if (!$user) {
            return redirect('/login.html?error=user_not_found');
        }

        $user->github_token = $accessToken;
        $user->github_username = $userInfo['login'] ?? null;
        $user->save();

        $this->logActivity('github_connect', $user->id, 'Connected GitHub account');

        return redirect('/student/dashboard.html?github_connected=success');
    }

    /**
     * Get GitHub OAuth redirect URL (JSON response for frontend)
     */
    public function githubOAuthRedirect()
    {
        $clientId = env('GITHUB_CLIENT_ID');
        $redirectUri = env('GITHUB_REDIRECT_URI', url('/api/auth/github/callback'));
        $scope = 'repo,user:email';
        $state = csrf_token();
        
        $url = "https://github.com/login/oauth/authorize?client_id={$clientId}&redirect_uri=" . urlencode($redirectUri) . "&scope={$scope}&state={$state}";
        
        return response()->json([
            'success' => true,
            'url' => $url
        ]);
    }

    /**
     * Sync GitHub repository data
     */
    public function syncGitHub(Request $request)
    {
        $user = $request->user();
        
        if (!$user->hasGithubConnected()) {
            return response()->json([
                'success' => false,
                'message' => 'No GitHub repository connected'
            ], 400);
        }
        
        // Calculate contributions
        $this->calculateContributions($user);
        
        return response()->json([
            'success' => true,
            'message' => 'Repository synced successfully'
        ]);
    }

    /**
     * Disconnect GitHub repository
     */
    public function disconnectGitHub(Request $request)
    {
        $user = $request->user();
        
        $user->github_token = null;
        $user->github_username = null;
        $user->github_repo_url = null;
        $user->github_connected_at = null;
        $user->save();
        
        return response()->json([
            'success' => true,
            'message' => 'GitHub disconnected successfully'
        ]);
    }

    // ============================================
    // GITHUB CONNECTION (আপডেটেড)
    // ============================================

    public function connectGitHub(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'repo_url' => 'required|url|regex:/^https?:\/\/github\.com\/[a-zA-Z0-9-]+\/[a-zA-Z0-9-._]+$/',
            'repo_type' => 'nullable|string|in:original,collaborator,forked'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Check if user has GitHub token
        if (empty($user->github_token)) {
            return response()->json([
                'success' => false,
                'message' => 'Please connect your GitHub account first using the "Connect GitHub Account" button above.'
            ], 400);
        }

        // Extract repo path from URL
        $repoPath = $this->extractRepoPath($request->repo_url);
        if (!$repoPath) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid GitHub repository URL'
            ], 400);
        }

        // Verify repository exists and user has access
        try {
            $response = Http::withHeaders([
                'Authorization' => "token {$user->github_token}",
                'Accept' => 'application/vnd.github.v3+json'
            ])->get("https://api.github.com/repos/{$repoPath}");

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Repository not found or you don\'t have access. Make sure the repository exists and you have connected your GitHub account.'
                ], 400);
            }

            $repoData = $response->json();
            
            // Save repository URL
            $user->github_repo_url = $request->repo_url;
            $user->github_connected_at = now();
            $user->save();

            // Calculate contributions
            $this->calculateContributions($user);

            return response()->json([
                'success' => true,
                'message' => 'Repository connected successfully!',
                'data' => [
                    'repo_url' => $user->github_repo_url,
                    'repo_name' => $repoData['full_name'] ?? $repoPath,
                    'connected_at' => $user->github_connected_at
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('GitHub connection error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect repository: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getGitHubRepoDetails(Request $request)
    {
        $user = $request->user();

        if (!$user->hasGithubConnected()) {
            return response()->json(['error' => 'No repository connected'], 404);
        }

        $repoPath = $user->github_repo_path;
        if (!$repoPath) {
            return response()->json(['error' => 'Invalid repository path'], 400);
        }

        $token = $user->github_token;

        try {
            $response = Http::withHeaders([
                'Authorization' => "token {$token}",
                'Accept' => 'application/vnd.github.v3+json'
            ])->get("https://api.github.com/repos/{$repoPath}");

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['error' => 'Failed to fetch repository details'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
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

        $score = ($gitHubMetrics * ($weightage['commits'] ?? 25) / 100) +
                 ($attendanceScore * ($weightage['attendance'] ?? 25) / 100) +
                 ($peerReviews * ($weightage['peer_reviews'] ?? 25) / 100) +
                 ($workingHours * ($weightage['working_hours'] ?? 25) / 100);

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

        $weeklyData = $this->getWeeklyData($id);
        $dailyActivity = $this->getDailyActivity($id);
        $scoreBreakdown = $this->getScoreBreakdown($id);

        return response()->json([
            'success' => true,
            'total_commits' => $scores->sum('commits') ?? 0,
            'total_prs' => $scores->sum('pull_requests') ?? 0,
            'total_lines_added' => $scores->sum('lines_added') ?? 0,
            'total_lines_deleted' => $scores->sum('lines_deleted') ?? 0,
            'activity_consistency_score' => $scores->avg('score') ?? 0,
            'team_rank' => $this->calculateTeamRank($id, $scores->first()?->group_id ?? null),
            'contribution_percentage' => $scores->avg('score') ?? 0,
            'weekly_data' => $weeklyData,
            'daily_activity' => $dailyActivity,
            'score_breakdown' => $scoreBreakdown,
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
            'total_commits' => $members->sum(function($m) { return $m->contributionScores->sum('commits'); }),
            'weekly_scores' => [65, 70, 75, round($avgScore, 2)],
            'members' => $members->map(function($m) {
                $score = $m->contributionScores->first();
                return [
                    'id' => $m->id,
                    'name' => $m->first_name . ' ' . $m->last_name,
                    'contribution_percentage' => round($m->contributionScores->avg('score') ?? 0, 2),
                    'classification' => $this->getClassification($m->contributionScores->avg('score') ?? 0),
                    'commits' => $score->commits ?? 0,
                    'prs' => $score->pull_requests ?? 0,
                    'lines_added' => $score->lines_added ?? 0
                ];
            })
        ]);
    }

    public function evaluateStudent($id)
    {
        $scores = ContributionScore::where('student_id', $id)->get();
        $avgScore = $scores->avg('score') ?? 0;
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
            'weekly_scores' => $this->getWeeklyScores($id)
        ]);
    }

    private function getWeeklyScores($id)
    {
        $scores = ContributionScore::where('student_id', $id)
            ->latest('calculated_at')
            ->take(8)
            ->get()
            ->reverse()
            ->pluck('score')
            ->toArray();

        while (count($scores) < 8) {
            array_unshift($scores, 0);
        }

        return $scores;
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
            'Active' => 'Excellent contribution quality and consistency! You\'re demonstrating strong collaboration skills.',
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
    // REPORT GENERATION METHODS
    // ============================================

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
            'total_commits' => $scores->sum('commits') ?? 0,
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
}