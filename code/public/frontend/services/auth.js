// Authentication Service
import APIClient from './api.js';

export class AuthService {
    constructor() {
        this.api = new APIClient();
    }

    async login(email, password) {
        const response = await this.api.post('/auth/login', { email, password });
        if (response.token) {
            this.api.setToken(response.token);
            localStorage.setItem('groupsync_user_data', JSON.stringify(response.user));
            return response;
        }
        throw new Error('Login failed');
    }

    async register(userData) {
        const response = await this.api.post('/auth/register', userData);
        return response;
    }

    async logout() {
        try {
            await this.api.post('/auth/logout');
        } catch (error) {
            console.error('Logout error:', error);
        } finally {
            this.api.setToken(null);
            localStorage.removeItem('groupsync_user_data');
            localStorage.removeItem('groupsync_auth_token');
        }
    }

    async refreshToken() {
        const response = await this.api.post('/auth/refresh');
        if (response.token) {
            this.api.setToken(response.token);
            return response.token;
        }
        throw new Error('Token refresh failed');
    }

    async getCurrentUser() {
        const response = await this.api.get('/auth/me');
        if (response.user) {
            localStorage.setItem('groupsync_user_data', JSON.stringify(response.user));
            return response.user;
        }
        return null;
    }

    async githubConnect(code) {
        const response = await this.api.post('/auth/github/connect', { code });
        return response;
    }

    async changePassword(currentPassword, newPassword) {
        return await this.api.post('/auth/change-password', {
            current_password: currentPassword,
            new_password: newPassword
        });
    }

    async forgotPassword(email) {
        return await this.api.post('/auth/forgot-password', { email });
    }

    async resetPassword(token, password) {
        return await this.api.post('/auth/reset-password', {
            token,
            password
        });
    }

    isAuthenticated() {
        return !!this.api.getToken();
    }

    getUser() {
        const user = localStorage.getItem('groupsync_user_data');
        return user ? JSON.parse(user) : null;
    }

    hasRole(role) {
        const user = this.getUser();
        return user && user.role === role;
    }
}

export default AuthService;