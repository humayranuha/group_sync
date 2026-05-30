import { setState, getState } from './state.js';
import { AuthService } from '../services/auth.js';
import { CourseService } from '../services/courseService.js';
import { GroupService } from '../services/groupService.js';
import { AnalyticsService } from '../services/analyticsService.js';
import { AIService } from '../services/aiService.js';

const authService = new AuthService();
const courseService = new CourseService();
const groupService = new GroupService();
const analyticsService = new AnalyticsService();
const aiService = new AIService();

// User Actions
export async function loginUser(email, password) {
    setState({ loading: true });
    try {
        const response = await authService.login(email, password);
        setState({
            user: response.user,
            isAuthenticated: true,
            userRole: response.user.role,
            loading: false
        });
        return response;
    } catch (error) {
        setState({ loading: false });
        throw error;
    }
}

export function logoutUser() {
    authService.logout();
    setState({
        user: null,
        isAuthenticated: false,
        userRole: null,
        courses: [],
        groups: [],
        repositories: [],
        analytics: null,
        aiEvaluations: []
    });
}

// Course Actions
export async function loadCourses() {
    const state = getState();
    if (!state.user) return;
    
    setState({ loading: true });
    try {
        let courses;
        if (state.userRole === 'professor') {
            courses = await courseService.getCourses(state.user.id);
        } else {
            courses = await courseService.getStudentCourses(state.user.id);
        }
        setState({ courses, loading: false });
        return courses;
    } catch (error) {
        setState({ loading: false });
        throw error;
    }
}

export async function createCourse(courseData) {
    setState({ loading: true });
    try {
        const course = await courseService.createCourse(courseData);
        const state = getState();
        setState({
            courses: [...state.courses, course],
            loading: false
        });
        return course;
    } catch (error) {
        setState({ loading: false });
        throw error;
    }
}

export async function updateCourse(courseId, courseData) {
    setState({ loading: true });
    try {
        const updated = await courseService.updateCourse(courseId, courseData);
        const state = getState();
        setState({
            courses: state.courses.map(c => c.id === courseId ? updated : c),
            loading: false
        });
        return updated;
    } catch (error) {
        setState({ loading: false });
        throw error;
    }
}

export async function deleteCourse(courseId) {
    setState({ loading: true });
    try {
        await courseService.deleteCourse(courseId);
        const state = getState();
        setState({
            courses: state.courses.filter(c => c.id !== courseId),
            loading: false
        });
    } catch (error) {
        setState({ loading: false });
        throw error;
    }
}

// Group Actions
export async function loadGroups(courseId) {
    setState({ loading: true });
    try {
        const groups = await groupService.getCourseGroups(courseId);
        setState({ groups, loading: false });
        return groups;
    } catch (error) {
        setState({ loading: false });
        throw error;
    }
}

export async function createGroup(groupData) {
    setState({ loading: true });
    try {
        const group = await groupService.createGroup(groupData);
        const state = getState();
        setState({
            groups: [...state.groups, group],
            loading: false
        });
        return group;
    } catch (error) {
        setState({ loading: false });
        throw error;
    }
}

export async function updateGroup(groupId, groupData) {
    setState({ loading: true });
    try {
        const updated = await groupService.updateGroup(groupId, groupData);
        const state = getState();
        setState({
            groups: state.groups.map(g => g.id === groupId ? updated : g),
            loading: false
        });
        return updated;
    } catch (error) {
        setState({ loading: false });
        throw error;
    }
}

// Analytics Actions
export async function loadGroupAnalytics(groupId) {
    setState({ loading: true });
    try {
        const analytics = await analyticsService.getGroupAnalytics(groupId);
        setState({ analytics, loading: false });
        return analytics;
    } catch (error) {
        setState({ loading: false });
        throw error;
    }
}

export async function loadStudentAnalytics(studentId, groupId) {
    setState({ loading: true });
    try {
        const analytics = await analyticsService.getStudentAnalytics(studentId, groupId);
        setState({ analytics, loading: false });
        return analytics;
    } catch (error) {
        setState({ loading: false });
        throw error;
    }
}

// AI Actions
export async function loadAIEvaluation(studentId, groupId) {
    setState({ loading: true });
    try {
        const evaluation = await aiService.getStudentEvaluation(studentId, groupId);
        const state = getState();
        setState({
            aiEvaluations: [...state.aiEvaluations, evaluation],
            loading: false
        });
        return evaluation;
    } catch (error) {
        setState({ loading: false });
        throw error;
    }
}

export async function detectFreeRiders(groupId) {
    setState({ loading: true });
    try {
        const freeRiders = await aiService.detectFreeRiders(groupId);
        setState({ loading: false });
        return freeRiders;
    } catch (error) {
        setState({ loading: false });
        throw error;
    }
}

// UI Actions
export function toggleSidebar() {
    const state = getState();
    setState({ sidebarOpen: !state.sidebarOpen });
}

export function setTheme(theme) {
    setState({ theme });
    localStorage.setItem('theme', theme);
}

export function addNotification(notification) {
    const state = getState();
    setState({
        notifications: [notification, ...state.notifications],
        unreadCount: state.unreadCount + 1
    });
}

export function markNotificationRead(notificationId) {
    const state = getState();
    setState({
        notifications: state.notifications.map(n => 
            n.id === notificationId ? { ...n, read: true } : n
        ),
        unreadCount: Math.max(0, state.unreadCount - 1)
    });
}

export function clearNotifications() {
    setState({
        notifications: [],
        unreadCount: 0
    });
}

// Export all actions
export default {
    loginUser,
    logoutUser,
    loadCourses,
    createCourse,
    updateCourse,
    deleteCourse,
    loadGroups,
    createGroup,
    updateGroup,
    loadGroupAnalytics,
    loadStudentAnalytics,
    loadAIEvaluation,
    detectFreeRiders,
    toggleSidebar,
    setTheme,
    addNotification,
    markNotificationRead,
    clearNotifications
};