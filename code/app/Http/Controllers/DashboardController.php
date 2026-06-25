<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use App\Models\Group;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStats()
    {
        $stats = [
            'total_students' => User::where('role', 'student')->count(),
            'total_courses' => Course::count(),
            'total_groups' => Group::count(),
            'total_projects' => Project::count(),
        ];
        
        return response()->json($stats);
    }

    public function getStudents()
    {
        $students = User::where('role', 'student')
            ->select('id', 'first_name', 'last_name', 'email', 'classification', 'overall_score')
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->first_name . ' ' . $student->last_name,
                    'email' => $student->email,
                    'classification' => $student->classification ?? 'Moderate',
                    'score' => $student->overall_score ?? 50,
                ];
            });
        
        return response()->json($students);
    }

    public function getCourses()
    {
        // Demo data - আপনার actual Course model অনুযায়ী পরিবর্তন করুন
        $courses = [
            ['id' => 1, 'code' => 'CSE3104', 'title' => 'Software Engineering', 'semester' => 'Spring 2026', 'students' => 32],
            ['id' => 2, 'code' => 'CSE3105', 'title' => 'Database Systems', 'semester' => 'Spring 2026', 'students' => 28],
            ['id' => 3, 'code' => 'CSE3106', 'title' => 'Web Development', 'semester' => 'Spring 2026', 'students' => 25],
        ];
        
        return response()->json($courses);
    }

    public function getGroups()
    {
        $groups = Group::all()->map(function ($group) {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'members' => $group->members_count ?? 0,
                'contribution' => $group->contribution_percentage ?? 50,
            ];
        });
        
        return response()->json($groups);
    }

    public function getProjects()
    {
        $projects = Project::all()->map(function ($project) {
            return [
                'id' => $project->id,
                'title' => $project->title,
                'status' => $project->status ?? 'active',
                'deadline' => $project->deadline,
            ];
        });
        
        return response()->json($projects);
    }
}