<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Course;
use App\Models\Group;
use App\Models\Assignment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create a professor
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

        // 3. Create a course
        $course = Course::create([
            'code' => 'CS401',
            'title' => 'Software Engineering',
            'semester' => 'Fall 2024',
            'description' => 'Introduction to software engineering principles and practices.',
            'teacher_id' => $professor->id,
            'enrollment_code' => 'SE2024',
            'is_active' => true,
        ]);

        // 4. Enroll students in the course
        $course->students()->attach([
            $student1->id => ['enrolled_at' => now()],
            $student2->id => ['enrolled_at' => now()],
            $student3->id => ['enrolled_at' => now()],
        ]);

        // 5. Create a group
        $group = Group::create([
            'name' => 'Team Alpha',
            'course_id' => $course->id,
            'invitation_code' => 'ALPHA123',
            'max_members' => 5,
            'created_by' => $student1->id,
            'status' => 'active',
            'github_repo_url' => 'https://github.com/team-alpha/repo',
        ]);

        // 6. Add members to the group
        $group->members()->attach([
            $student1->id => ['joined_at' => now()],
            $student2->id => ['joined_at' => now()],
            $student3->id => ['joined_at' => now()],
        ]);

        // 7. Create an assignment
        Assignment::create([
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

        $this->command->info('✅ Demo data seeded successfully!');
        $this->command->info('👨‍🏫 Professor: prof@demo.com / 123456');
        $this->command->info('👩‍🎓 Students: alice@demo.com, bob@demo.com, charlie@demo.com / 123456');
    }
}