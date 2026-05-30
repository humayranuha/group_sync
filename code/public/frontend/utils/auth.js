// Authentication Utilities

// Get auth token from storage
export function getAuthToken() {
    return localStorage.getItem('groupsync_auth_token');
}

// Set auth token in storage
export function setAuthToken(token) {
    if (token) {
        localStorage.setItem('groupsync_auth_token', token);
    } else {
        localStorage.removeItem('groupsync_auth_token');
    }
}

// Get user data from storage
export function getUserData() {
    const user = localStorage.getItem('groupsync_user_data');
    if (user) {
        try {
            return JSON.parse(user);
        } catch {
            return null;
        }
    }
    return null;
}

// Set user data in storage
export function setUserData(user) {
    if (user) {
        localStorage.setItem('groupsync_user_data', JSON.stringify(user));
    } else {
        localStorage.removeItem('groupsync_user_data');
    }
}

// Check if user is authenticated
export function isAuthenticated() {
    const token = getAuthToken();
    const user = getUserData();
    return !!(token && user);
}

// Check if user has specific role
export function hasRole(role) {
    const user = getUserData();
    return user && user.role === role;
}

// Check if user is student
export function isStudent() {
    return hasRole('student');
}

// Check if user is professor
export function isProfessor() {
    return hasRole('professor');
}

// Check if user is admin
export function isAdmin() {
    return hasRole('admin');
}

// Clear all auth data (logout)
export function clearAuthData() {
    localStorage.removeItem('groupsync_auth_token');
    localStorage.removeItem('groupsync_user_data');
    localStorage.removeItem('groupsync_theme');
    localStorage.removeItem('groupsync_sidebar_state');
}

// Get user full name
export function getUserFullName() {
    const user = getUserData();
    if (user) {
        return `${user.first_name || ''} ${user.last_name || ''}`.trim();
    }
    return 'User';
}

// Get user display name
export function getUserDisplayName() {
    const user = getUserData();
    if (user) {
        return user.first_name || user.email || 'User';
    }
    return 'User';
}

// Get user avatar URL
export function getUserAvatar() {
    const user = getUserData();
    if (user && user.avatar) {
        return user.avatar;
    }
    return `https://ui-avatars.com/api/?name=${encodeURIComponent(getUserDisplayName())}&background=2563eb&color=fff`;
}

export default {
    getAuthToken,
    setAuthToken,
    getUserData,
    setUserData,
    isAuthenticated,
    hasRole,
    isStudent,
    isProfessor,
    isAdmin,
    clearAuthData,
    getUserFullName,
    getUserDisplayName,
    getUserAvatar
};