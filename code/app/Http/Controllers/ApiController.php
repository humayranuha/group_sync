<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ApiController extends Controller
{
    // Login API
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('auth-token')->plainTextToken;
            
            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'role' => $user->role
                ],
                'redirect' => $user->role === 'professor' ? '/professor/dashboard.html' : '/student/dashboard.html'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }
    
    // Register API
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|in:student,professor'
        ]);
        
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role']
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'user' => $user
        ]);
    }
    
    // Logout API
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true]);
    }
    
    // Get current user
    public function user(Request $request)
    {
        return response()->json($request->user());
    }
    
    // Courses
    public function getCourses()
    {
        return response()->json([
            ['id' => 1, 'code' => 'CS401', 'name' => 'Software Engineering', 'semester' => 'Fall 2024'],
            ['id' => 2, 'code' => 'CS402', 'name' => 'Database Systems', 'semester' => 'Fall 2024'],
            ['id' => 3, 'code' => 'CS403', 'name' => 'Web Development', 'semester' => 'Fall 2024'],
        ]);
    }
    
    public function getProfessorCourses($id)
    {
        return response()->json([
            ['id' => 1, 'code' => 'CS401', 'name' => 'Software Engineering', 'student_count' => 32, 'group_count' => 5],
            ['id' => 2, 'code' => 'CS402', 'name' => 'Database Systems', 'student_count' => 28, 'group_count' => 4],
        ]);
    }
    
    public function getStudentCourses($id)
    {
        return response()->json([
            ['id' => 1, 'code' => 'CS401', 'name' => 'Software Engineering', 'progress' => 75],
        ]);
    }
    
    // Groups
    public function getGroups()
    {
        return response()->json([
            ['id' => 1, 'name' => 'Team Alpha', 'course_name' => 'CS401', 'member_count' => 4],
            ['id' => 2, 'name' => 'Team Beta', 'course_name' => 'CS401', 'member_count' => 4],
        ]);
    }
    
    public function getGroup($id)
    {
        return response()->json([
            'id' => $id,
            'name' => 'Team Alpha',
            'course_name' => 'Software Engineering',
            'member_count' => 4
        ]);
    }
    
    public function getGroupMembers($id)
    {
        return response()->json([
            ['id' => 1, 'name' => 'John Doe', 'role' => 'Student', 'contribution_percentage' => 35, 'classification' => 'Active', 'commits' => 45],
            ['id' => 2, 'name' => 'Jane Smith', 'role' => 'Student', 'contribution_percentage' => 28, 'classification' => 'Active', 'commits' => 32],
            ['id' => 3, 'name' => 'Mike Brown', 'role' => 'Student', 'contribution_percentage' => 22, 'classification' => 'Moderate', 'commits' => 18],
            ['id' => 4, 'name' => 'Lisa Wong', 'role' => 'Student', 'contribution_percentage' => 15, 'classification' => 'Passive', 'commits' => 8],
        ]);
    }
    
    // Analytics
    public function getStudentAnalytics($id)
    {
        return response()->json([
            'total_commits' => 127,
            'total_prs' => 23,
            'total_lines_added' => 2450,
            'total_lines_deleted' => 890,
            'activity_consistency_score' => 85,
            'team_rank' => 2,
            'contribution_percentage' => 35,
            'weekly_data' => [
                ['week' => 1, 'commits' => 12],
                ['week' => 2, 'commits' => 19],
                ['week' => 3, 'commits' => 15],
                ['week' => 4, 'commits' => 27],
            ],
            'daily_activity' => [5, 8, 12, 7, 9, 3, 2]
        ]);
    }
    
    public function getGroupAnalytics($id)
    {
        return response()->json([
            'total_contributions' => 98,
            'avg_activity' => 78,
            'team_performance' => 82,
            'total_commits' => 98,
            'weekly_scores' => [65, 70, 75, 82],
        ]);
    }
    
    // AI Evaluation
    public function evaluateStudent($id)
    {
        return response()->json([
            'classification' => 'Active',
            'participation_score' => 85,
            'quality_score' => 78,
            'consistency_score' => 92,
            'overall_score' => 85,
            'feedback' => 'Excellent contribution quality and consistency! You\'re demonstrating strong collaboration skills.',
            'suggestions' => [
                'Increase code review participation',
                'Help mentor passive group members',
                'Document your code more thoroughly'
            ]
        ]);
    }
    
    public function evaluateGroup($id)
    {
        return response()->json([
            'classification' => 'Good',
            'feedback' => 'Team is performing well overall.',
            'recommendations' => ['Schedule regular team meetings', 'Pair program for complex tasks']
        ]);
    }
    
    public function detectFreeRiders($id)
    {
        return response()->json([
            ['student_name' => 'Mike Brown', 'severity' => 'Medium', 'message' => 'Low contribution for 2 weeks'],
            ['student_name' => 'Lisa Wong', 'severity' => 'High', 'message' => 'Minimal commits in last 3 weeks']
        ]);
    }
    
    // GitHub Integration
    public function getRepositories()
    {
        return response()->json([
            ['id' => 1, 'name' => 'project-repo', 'url' => 'https://github.com/student/project', 'type' => 'original']
        ]);
    }
    
    public function connectRepository(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Repository connected']);
    }
    
    public function syncRepository($id)
    {
        return response()->json(['success' => true, 'message' => 'Repository synced']);
    }
}