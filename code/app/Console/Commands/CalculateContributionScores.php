<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assignment;
use App\Models\Group;
use App\Models\User;
use App\Models\ContributionScore;
use App\Models\PeerReview;
use App\Models\Attendance;
use App\Models\WorkingHour;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalculateContributionScores extends Command
{
    protected $signature = 'contribution:calculate';
    protected $description = 'Calculate contribution scores for all students based on latest metrics';

    public function handle()
    {
        $this->info('🔄 Starting contribution score calculation...');

        // Get all active assignments
        $assignments = Assignment::where('status', 'active')->get();

        if ($assignments->isEmpty()) {
            $this->warn('No active assignments found.');
            return;
        }

        foreach ($assignments as $assignment) {
            $this->info("📘 Processing assignment: {$assignment->title}");

            // Get all groups for this assignment's course
            $groups = Group::where('course_id', $assignment->course_id)->get();

            foreach ($groups as $group) {
                $this->info("👥 Group: {$group->name}");

                $members = $group->members;

                foreach ($members as $student) {
                    $this->line("   - Student: {$student->first_name} {$student->last_name}");

                    // Fetch metrics
                    $gitHubScore = $this->getGitHubMetrics($student->id, $assignment->id, $group->id);
                    $attendanceScore = $this->getAttendance($student->id, $assignment->id);
                    $peerReviewScore = $this->getPeerReviewScore($student->id, $assignment->id);
                    $workingHoursScore = $this->getWorkingHours($student->id, $assignment->id);

                    $weightage = json_decode($assignment->weightage, true);

                    // Calculate weighted score
                    $score = ($gitHubScore * $weightage['commits'] / 100) +
                             ($attendanceScore * $weightage['attendance'] / 100) +
                             ($peerReviewScore * $weightage['peer_reviews'] / 100) +
                             ($workingHoursScore * $weightage['working_hours'] / 100);

                    $score = round($score, 2);

                    $status = 'normal';
                    if ($score < 30) $status = 'critical';
                    elseif ($score < 50) $status = 'warning';

                    // Save or update
                    ContributionScore::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'assignment_id' => $assignment->id,
                        ],
                        [
                            'group_id' => $group->id,
                            'score' => $score,
                            'status' => $status,
                            'breakdown' => json_encode([
                                'github_commits' => $gitHubScore,
                                'attendance' => $attendanceScore,
                                'peer_reviews' => $peerReviewScore,
                                'working_hours' => $workingHoursScore,
                                'weightage_used' => $weightage,
                            ]),
                            'calculated_at' => now(),
                        ]
                    );

                    // Optionally notify professor if critical
                    if ($status === 'critical') {
                        $this->notifyProfessor($student, $assignment, $score);
                    }
                }
            }
        }

        $this->info('✅ Contribution scores calculated successfully!');
        Log::info('Contribution scores calculated by scheduler');
    }

    // ===== Helper methods (copy from ApiController) =====
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
            Log::warning("GitHub API error for user {$studentId}: " . $e->getMessage());
            return 0;
        }
    }

    private function getAttendance($studentId, $assignmentId)
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

    private function notifyProfessor($student, $assignment, $score)
    {
        // In a real app, send email or notification
        // For now, just log
        Log::info("Low score alert: {$student->first_name} {$student->last_name} scored {$score}% in assignment {$assignment->title}");
    }
}