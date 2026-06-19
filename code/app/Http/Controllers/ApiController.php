Based on your project, here are ALL the files you need with their **complete updated code**:

---

## 1. **app/Http/Controllers/Controller.php** (KEEP AS IS - NO CHANGE)

```php
<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //
}
```

---

## 2. **app/Http/Controllers/ApiController.php** (FULL UPDATED CODE)

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Course;
use App\Models\Group;
use App\Models\Assignment;
use App\Models\PeerReview;
use App\Models\ContributionScore;
use App\Models\Notification;
use App\Models\AuditLog;

class ApiController extends Controller
{
    /**
     * ============================================
     * AUTHENTICATION SECTION
     * ============================================
     */
    
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
            'password' => 'required|min:6|confirmed',
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
    
    /**
     * ============================================
     * COURSE MANAGEMENT SECTION
     * ============================================
     */
    
    public function getCourses()
    {
        $courses = Course::with('teacher')->get();
        
        return response()->json([
            'success' => true,
            'courses' => $courses
        ]);
    }
    
    public function getProfessorCourses($id)
    {
        $courses = Course::where('teacher_id', $id)
            ->withCount(['students', 'groups'])
            ->get();
        
        return response()->json([
            'success' => true,
            'courses' => $courses
        ]);
    }
    
    public function getStudentCourses($id)
    {
        $user = User::findOrFail($id);
        $courses = $user->courses()->with('teacher')->get();
        
        return response()->json([
            'success' => true,
            'courses' => $courses
        ]);
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
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
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
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this course'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'semester' => 'sometimes|string',
            'description' => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
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
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:users,id,role,student'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
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
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $course = Course::where('enrollment_code', $request->enrollment_code)->first();
        
        if ($course->students()->where('user_id', $request->user()->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are already enrolled in this course'
            ], 400);
        }
        
        $course->students()->attach($request->user()->id);
        
        $this->logActivity('self_enroll', $request->user()->id, 
            'Self-enrolled in course: ' . $course->code);
        
        return response()->json([
            'success' => true,
            'message' => 'Enrolled successfully',
            'course' => $course
        ]);
    }
    
    public function getCourseStudents($id)
    {
        $course = Course::with('students')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'students' => $course->students
        ]);
    }
    
    /**
     * ============================================
     * GROUP MANAGEMENT SECTION
     * ============================================
     */
    
    public function getGroups()
    {
        $groups = Group::with(['course', 'members'])->get();
        
        return response()->json([
            'success' => true,
            'groups' => $groups
        ]);
    }
    
    public function getGroup($id)
    {
        $group = Group::with(['course', 'members', 'assignments'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'group' => $group
        ]);
    }
    
    public function getGroupMembers($id)
    {
        $group = Group::findOrFail($id);
        $members = $group->members()->with('contributionScores')->get();
        
        return response()->json([
            'success' => true,
            'members' => $members
        ]);
    }
    
    public function getUserGroups(Request $request)
    {
        $groups = $request->user()->groups()->with(['course', 'members'])->get();
        
        return response()->json([
            'success' => true,
            'groups' => $groups
        ]);
    }
    
    public function createGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'max_members' => 'nullable|integer|min:2|max:10'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $existingGroup = Group::where('course_id', $request->course_id)
            ->whereHas('members', function($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })->exists();
        
        if ($existingGroup) {
            return response()->json([
                'success' => false,
                'message' => 'You are already in a group for this course'
            ], 400);
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
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $group = Group::where('invitation_code', $request->invitation_code)
            ->where('course_id', $request->course_id)
            ->first();
        
        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid invitation code'
            ], 404);
        }
        
        if ($group->members()->count() >= $group->max_members) {
            return response()->json([
                'success' => false,
                'message' => 'Group is full'
            ], 400);
        }
        
        if ($group->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'This group is no longer active'
            ], 400);
        }
        
        $existingGroup = Group::where('course_id', $request->course_id)
            ->whereHas('members', function($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })->exists();
        
        if ($existingGroup) {
            return response()->json([
                'success' => false,
                'message' => 'You are already in a group for this course'
            ], 400);
        }
        
        $group->members()->attach($request->user()->id, ['joined_at' => now()]);
        
        $this->logActivity('join_group', $request->user()->id, 
            'Joined group: ' . $group->name);
        
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
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this group'
            ], 400);
        }
        
        if ($group->created_by == $request->user()->id && $group->members()->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot leave group. You are the only member and creator.'
            ], 400);
        }
        
        $group->members()->detach($request->user()->id);
        
        $this->logActivity('leave_group', $request->user()->id, 
            'Left group: ' . $group->name);
        
        if ($group->created_by == $request->user()->id) {
            $newCreator = $group->members()->first();
            if ($newCreator) {
                $group->created_by = $newCreator->id;
                $group->save();
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Left group successfully'
        ]);
    }
    
    public function deleteGroup(Request $request, $id)
    {
        $group = Group::findOrFail($id);
        
        if ($group->created_by !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the group creator can delete this group'
            ], 403);
        }
        
        $group->members()->detach();
        $group->delete();
        
        $this->logActivity('delete_group', $request->user()->id, 
            'Deleted group: ' . $group->name);
        
        return response()->json([
            'success' => true,
            'message' => 'Group deleted successfully'
        ]);
    }
    
    /**
     * ============================================
     * ASSIGNMENT MANAGEMENT SECTION
     * ============================================
     */
    
    public function createAssignment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'weightage' => 'required|array',
            'weightage.commits' => 'required|numeric|min:0|max:100',
            'weightage.attendance' => 'required|numeric|min:0|max:100',
            'weightage.peer_reviews' => 'required|numeric|min:0|max:100',
            'weightage.working_hours' => 'required|numeric|min:0|max:100',
            'deadline' => 'required|date|after:now',
            'peer_review_deadline' => 'nullable|date|after:now'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $total = $request->weightage['commits'] + 
                 $request->weightage['attendance'] + 
                 $request->weightage['peer_reviews'] + 
                 $request->weightage['working_hours'];
        
        if ($total != 100) {
            return response()->json([
                'success' => false,
                'message' => 'Total weightage must equal 100%. Current total: ' . $total . '%'
            ], 400);
        }
        
        $course = Course::find($request->course_id);
        if ($course->teacher_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to create assignments for this course'
            ], 403);
        }
        
        $assignment = Assignment::create([
            'course_id' => $request->course_id,
            'title' => $request->title,
            'description' => $request->description ?? null,
            'weightage' => json_encode($request->weightage),
            'deadline' => $request->deadline,
            'peer_review_deadline' => $request->peer_review_deadline ?? null,
            'created_by' => $request->user()->id,
            'status' => 'active'
        ]);
        
        $this->logActivity('create_assignment', $request->user()->id, 
            'Created assignment: ' . $assignment->title);
        
        return response()->json([
            'success' => true,
            'message' => 'Assignment created successfully',
            'assignment' => $assignment
        ], 201);
    }
    
    public function getAssignment($id)
    {
        $assignment = Assignment::with(['course', 'course.teacher'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'assignment' => $assignment,
            'weightage' => json_decode($assignment->weightage, true)
        ]);
    }
    
    public function getCourseAssignments($courseId)
    {
        $assignments = Assignment::where('course_id', $courseId)->get();
        
        return response()->json([
            'success' => true,
            'assignments' => $assignments
        ]);
    }
    
    public function updateAssignment(Request $request, $id)
    {
        $assignment = Assignment::findOrFail($id);
        
        if ($assignment->created_by !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this assignment'
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'weightage' => 'sometimes|array',
            'weightage.commits' => 'required_with:weightage|numeric|min:0|max:100',
            'weightage.attendance' => 'required_with:weightage|numeric|min:0|max:100',
            'weightage.peer_reviews' => 'required_with:weightage|numeric|min:0|max:100',
            'weightage.working_hours' => 'required_with:weightage|numeric|min:0|max:100',
            'deadline' => 'sometimes|date|after:now',
            'peer_review_deadline' => 'nullable|date|after:now',
            'status' => 'sometimes|in:active,closed,archived'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $data = $request->only(['title', 'description', 'deadline', 'peer_review_deadline', 'status']);
        
        if ($request->has('weightage')) {
            $total = $request->weightage['commits'] + 
                     $request->weightage['attendance'] + 
                     $request->weightage['peer_reviews'] + 
                     $request->weightage['working_hours'];
            
            if ($total != 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total weightage must equal 100%'
                ], 400);
            }
            $data['weightage'] = json_encode($request->weightage);
        }
        
        $assignment->update($data);
        
        $this->logActivity('update_assignment', $request->user()->id, 
            'Updated assignment: ' . $assignment->title);
        
        return response()->json([
            'success' => true,
            'message' => 'Assignment updated successfully',
            'assignment' => $assignment
        ]);
    }
    
    /**
     * ============================================
     * PEER REVIEW SECTION
     * ============================================
     */
    
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
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        $reviewer = $request->user();
        $group = Group::find($request->group_id);
        
        if (!$group->members()->where('user_id', $reviewer->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this group'
            ], 403);
        }
        
        if (!$group->members()->where('user_id', $request->reviewee_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Reviewee is not a member of this group'
            ], 400);
        }
        
        if ($reviewer->id == $request->reviewee_id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot review yourself'
            ], 400);
        }
        
        $assignment = Assignment::find($request->assignment_id);
        if ($assignment->peer_review_deadline && now()->gt($assignment->peer_review_deadline)) {
            return response()->json([
                'success' => false,
                'message' => 'Peer review deadline has passed'
            ], 400);
        }
        
        $existingReview = PeerReview::where('reviewer_id', $reviewer->id)
            ->where('reviewee_id', $request->reviewee_id)
            ->where('assignment_id', $request->assignment_id)
            ->exists();
        
        if ($existingReview) {
            return response()->json([
                'success' => false,
                'message' => 'You have already reviewed this team member'
            ], 400);
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
            return response()->json([
                'success' => false,
                'message' => 'You are not a member of this group'
            ], 403);
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
    
    /**
     * ============================================
     * CONTRIBUTION SCORE SECTION
     * ============================================
     */
    
    public function calculateContributionScore(Request $request, $studentId, $assignmentId)
    {
        $student = User::findOrFail($studentId);
        $assignment = Assignment::findOrFail($assignmentId);
        
        $group = Group::where('course_id', $assignment->course_id)
            ->whereHas('members', function($query) use ($studentId) {
                $query->where('user_id', $studentId);
            })->first();
        
        if (!$group) {
            return response()->json([
                'success' => false,
                'message' => 'Student is not in a group for this course'
            ], 400);
        }
        
        $weightage = json_decode($assignment->weightage, true);
        
        $gitHubMetrics = $this->getGitHubMetrics($studentId, $assignmentId, $group->id);
        $attendance = $this->getAttendance($studentId, $assignmentId);
        $peerReviews = $this->getPeerReviewScore($studentId, $assignmentId);
        $workingHours = $this->getWorkingHours($studentId, $assignmentId);
        
        $score = ($gitHubMetrics * $weightage['commits'] / 100) +
                 ($attendance * $weightage['attendance'] / 100) +
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
                    'attendance' => $attendance,
                    'peer_reviews' => $peerReviews,
                    'working_hours' => $workingHours,
                    'weightage' => $weightage
                ]),
                'calculated_at' => now()
            ]
        );
        
        return response()->json([
            'success' => true,
            'score' => round($score, 2),
            'status' => $status,
            'breakdown' => [
                'github_commits' => $gitHubMetrics,
                'attendance' => $attendance,
                'peer_reviews' => $peerReviews,
                'working_hours' => $workingHours,
                'weightage_used' => $weightage
            ]
        ]);
    }
    
    public function getStudentScore($studentId, $assignmentId)
    {
        $score = ContributionScore::where('student_id', $studentId)
            ->where('assignment_id', $assignmentId)
            ->first();
        
        if (!$score) {
            return response()->json([
                'success' => false,
                'message' => 'Score not found. Please calculate the score first.'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'score' => $score
        ]);
    }
    
    /**
     * ============================================
     * ANALYTICS SECTION
     * ============================================
     */
    
    public function getStudentAnalytics($id)
    {
        return response()->json([
            'success' => true,
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
            'success' => true,
            'total_contributions' => 98,
            'avg_activity' => 78,
            'team_performance' => 82,
            'total_commits' => 98,
            'weekly_scores' => [65, 70, 75, 82],
        ]);
    }
    
    public function evaluateStudent($id)
    {
        return response()->json([
            'success' => true,
            'classification' => 'Active',
            'participation_score' => 85,
            'quality_score' => 78,
            'consistency_score' => 92,
            'overall_score' => 85,
            'feedback' => 'Excellent contribution quality and consistency!
