<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Course;
use App\Models\Group;
use App\Models\Assignment;
use App\Models\ContributionScore;  // 👈 যোগ করুন
use App\Models\Attendance;
use App\Models\WorkingHour;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run()
    {
        // 1. Create professor
        $professor = User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'prof@demo.com',
            'password' => Hash::make('123456'),
            'role' => 'professor',
            'department' => 'Computer Science',
        ]);

        // 2. Create students
        $student1 = User::create([
            'first_name' => 'Alice',
            'last_name' => 'Johnson',
            'email' => 'alice@demo.com',
            'password' => Hash::make('123456'),
            'role' => 'student',
            'department' => 'Computer Science',
        ]);

        $student2 = User::create([
            'first_name' => 'Bob',
            'last_name' => 'Smith',
            'email' => 'bob@demo.com',
            'password' => Hash::make('123456'),
            'role' => 'student',
            'department' => 'Computer Science',
        ]);

        $student3 = User::create([
            'first_name' => 'Charlie',
            'last_name' => 'Brown',
            'email' => 'charlie@demo.com',
            'password' => Hash::make('123456'),
            'role' => 'student',
            'department' => 'Computer Science',
        ]);
        // ===== ADD ADMIN USER =====
$admin = User::create([
    'first_name' => 'Admin',
    'last_name' => 'User',
    'email' => 'admin@demo.com',
    'password' => Hash::make('123456'),
    'role' => 'admin',
    'department' => 'Administration',
]);

        // 3. Create course
        $course = Course::create([
            'code' => 'CS401',
            'title' => 'Software Engineering',
            'semester' => 'Fall 2024',
            'description' => 'Introduction to software engineering principles and practices.',
            'teacher_id' => $professor->id,
            'enrollment_code' => 'SE2024',
            'is_active' => true,
        ]);

        // 4. Enroll students
        $course->students()->attach([
            $student1->id => ['enrolled_at' => now()],
            $student2->id => ['enrolled_at' => now()],
            $student3->id => ['enrolled_at' => now()],
        ]);

        // 5. Create group
        $group = Group::create([
            'name' => 'Team Alpha',
            'course_id' => $course->id,
            'invitation_code' => 'ALPHA123',
            'max_members' => 5,
            'created_by' => $student1->id,
            'status' => 'active',
            'github_repo_url' => 'https://github.com/team-alpha/repo',
        ]);

        // 6. Add members to group
        $group->members()->attach([
            $student1->id => ['joined_at' => now()],
            $student2->id => ['joined_at' => now()],
            $student3->id => ['joined_at' => now()],
        ]);

        // 7. Create assignment
        $assignment = Assignment::create([
            'course_id' => $course->id,
            'title' => 'Project 1: Requirement Analysis',
            'description' => 'Submit a detailed requirement analysis document.',
            'weightage' => json_encode([
                'commits' => 40,
                'attendance' => 20,
                'peer_reviews' => 20,
                'working_hours' => 20,
            ]),
            'deadline' => now()->addDays(14),
            'peer_review_deadline' => now()->addDays(10),
            'created_by' => $professor->id,
            'status' => 'active',
        ]);

        // ==========================================
        // ===== 8. Contribution Scores (NEW) =====
        // ==========================================

        $students = User::where('role', 'student')->get();
        $allAssignments = Assignment::all();  // 👈 আলাদা নাম ব্যবহার করুন

        foreach ($students as $student) {
            foreach ($allAssignments as $assign) {  // 👈 $assign ব্যবহার করুন
                $score = rand(30, 90);
                $status = 'normal';
                if ($score < 40) $status = 'critical';
                elseif ($score < 60) $status = 'warning';

                ContributionScore::create([
                    'student_id' => $student->id,
                    'assignment_id' => $assign->id,
                    'group_id' => $group->id,
                    'score' => $score,
                    'status' => $status,
                    'breakdown' => json_encode([
                        'github_commits' => rand(40, 90),
                        'attendance' => rand(50, 100),
                        'peer_reviews' => rand(40, 90),
                        'working_hours' => rand(30, 80)
                    ]),
                    'calculated_at' => now()
                ]);
            }
        }

        $this->command->info('✅ Contribution scores added for all students!');

        // ==========================================
        // ===== 9. Attendance & Working Hours =====
        // ==========================================

        $studentsList = [$student1, $student2, $student3];
        $assignmentId = $assignment->id;
        $courseId = $course->id;

        // Attendance: 10 random days per student
        foreach ($studentsList as $student) {
            for ($i = 0; $i < 10; $i++) {
                $date = Carbon::now()->subDays(rand(1, 30));
                $exists = Attendance::where('student_id', $student->id)
                    ->where('date', $date)
                    ->exists();
                if (!$exists) {
                    Attendance::create([
                        'student_id' => $student->id,
                        'course_id' => $courseId,
                        'date' => $date,
                        'present' => rand(0, 1) ? true : false,
                    ]);
                }
            }
        }

        // Working Hours: 5-10 entries per student
        foreach ($studentsList as $student) {
            $numEntries = rand(5, 10);
            for ($i = 0; $i < $numEntries; $i++) {
                $date = Carbon::now()->subDays(rand(0, 14));
                $hours = rand(1, 4) + round(rand(0, 10) / 10, 1);
                WorkingHour::create([
                    'student_id' => $student->id,
                    'assignment_id' => $assignmentId,
                    'date' => $date,
                    'hours' => $hours,
                ]);
            }
        }

        // ==========================================
        // ===== 10. Final Message =====
        // ==========================================

        $this->command->info('✅ Demo data seeded successfully!');
        $this->command->info('👨‍🏫 Professor: prof@demo.com / 123456');
        $this->command->info('👩‍🎓 Students: alice@demo.com, bob@demo.com, charlie@demo.com / 123456');
        $this->command->info('📊 Contribution Scores, Attendance & Working Hours added for each student.');
    }
}