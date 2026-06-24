// Protected Route Component
export class ProtectedRoute {
    constructor() {
        this.publicRoutes = [
            '/', '/index.html', '/landing.html', 
            '/login.html', '/register.html', 
            '/about.html', '/privacy.html', '/terms.html'
        ];
    }

    checkAuth() {
        const token = localStorage.getItem('groupsync_auth_token');
        const user = localStorage.getItem('groupsync_user_data');
        
        if (!token || !user) {
            return false;
        }
        
        try {
            const userData = JSON.parse(user);
            return !!(userData && userData.id);
        } catch {
            return false;
        }
    }

    getCurrentUser() {
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

    hasRole(requiredRoles) {
        const user = this.getCurrentUser();
        if (!user) return false;
        
        if (!requiredRoles || requiredRoles.length === 0) return true;
        
        return requiredRoles.includes(user.role);
    }

    redirectToLogin() {
        const currentPath = window.location.pathname;
        const loginUrl = `/login.html?redirect=${encodeURIComponent(currentPath)}`;
        window.location.href = loginUrl;
    }

    redirectToDashboard() {
        const user = this.getCurrentUser();
        if (user?.role === 'professor') {
            window.location.href = '/professor/dashboard.html';
        } else if (user?.role === 'student') {
            window.location.href = '/student/dashboard.html';
        } else {
            window.location.href = '/login.html';
        }
    }

    async protect(requiredRoles = []) {
        const isAuth = this.checkAuth();
        
        if (!isAuth) {
            this.redirectToLogin();
            return false;
        }
        
        if (requiredRoles.length > 0 && !this.hasRole(requiredRoles)) {
            this.redirectToDashboard();
            return false;
        }
        
        return true;
    }

    async protectRoute(routeConfig = {}) {
        const currentPath = window.location.pathname;
        const isPublicRoute = this.publicRoutes.includes(currentPath);
        
        if (isPublicRoute) {
            if (this.checkAuth()) {
                this.redirectToDashboard();
                return false;
            }
            return true;
        }
        
        return await this.protect(routeConfig.roles || []);
    }

    // Middleware for page protection
    requireAuth(redirectTo = '/login.html') {
        const token = localStorage.getItem('groupsync_auth_token');
        if (!token) {
            window.location.href = redirectTo;
            return false;
        }
        return true;
    }

    requireRole(role, redirectTo = '/dashboard.html') {
        const user = this.getCurrentUser();
        if (!user || user.role !== role) {
            window.location.href = redirectTo;
            return false;
        }
        return true;
    }

    // Check if current route matches pattern
    routeMatches(pattern, currentPath) {
        const patternParts = pattern.split('/');
        const pathParts = currentPath.split('/');
        
        if (patternParts.length !== pathParts.length) return false;
        
        for (let i = 0; i < patternParts.length; i++) {
            if (patternParts[i].startsWith(':')) continue;
            if (patternParts[i] !== pathParts[i]) return false;
        }
        
        return true;
    }

    // Extract route parameters
    getRouteParams(pattern, currentPath) {
        const params = {};
        const patternParts = pattern.split('/');
        const pathParts = currentPath.split('/');
        
        for (let i = 0; i < patternParts.length; i++) {
            if (patternParts[i].startsWith(':')) {
                const paramName = patternParts[i].substring(1);
                params[paramName] = pathParts[i];
            }
        }
        
        return params;
    }
}

// Export utility functions
export function requireAuth(redirectTo = '/login.html') {
    const protector = new ProtectedRoute();
    return protector.requireAuth(redirectTo);
}

export function requireRole(role, redirectTo = '/dashboard.html') {
    const protector = new ProtectedRoute();
    return protector.requireRole(role, redirectTo);
}

export function withProtection(requiredRoles = []) {
    return async function(targetFunction, ...args) {
        const protector = new ProtectedRoute();
        const isAllowed = await protector.protect(requiredRoles);
        
        if (isAllowed) {
            return targetFunction(...args);
        }
        
        return null;
    };
}

export default ProtectedRoute;