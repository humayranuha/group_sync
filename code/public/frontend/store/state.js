// Global Application State
export const AppState = {
    // User state
    user: null,
    isAuthenticated: false,
    userRole: null,
    
    // UI state
    sidebarOpen: false,
    theme: 'light',
    loading: false,
    
    // Data state
    courses: [],
    groups: [],
    repositories: [],
    analytics: null,
    aiEvaluations: [],
    
    // Notification state
    notifications: [],
    unreadCount: 0
};

// State subscribers
let subscribers = [];

// Subscribe to state changes
export function subscribe(callback) {
    subscribers.push(callback);
    return () => {
        subscribers = subscribers.filter(sub => sub !== callback);
    };
}

// Notify all subscribers
function notify() {
    subscribers.forEach(callback => callback(AppState));
}

// Update state
export function setState(updates) {
    Object.assign(AppState, updates);
    notify();
}

// Get current state
export function getState() {
    return { ...AppState };
}

// Reset state (for logout)
export function resetState() {
    Object.assign(AppState, {
        user: null,
        isAuthenticated: false,
        userRole: null,
        sidebarOpen: false,
        courses: [],
        groups: [],
        repositories: [],
        analytics: null,
        aiEvaluations: [],
        notifications: [],
        unreadCount: 0
    });
    notify();
}

export default { AppState, subscribe, setState, getState, resetState };