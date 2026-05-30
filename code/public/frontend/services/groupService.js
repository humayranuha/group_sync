// Group Service
import APIClient from './api.js';

export class GroupService {
    constructor() {
        this.api = new APIClient();
    }

    async getGroups() {
        return await this.api.get('/groups');
    }

    async getGroupById(groupId) {
        return await this.api.get(`/groups/${groupId}`);
    }

    async getCourseGroups(courseId) {
        return await this.api.get(`/courses/${courseId}/groups`);
    }

    async createGroup(groupData) {
        return await this.api.post('/groups', groupData);
    }

    async updateGroup(groupId, groupData) {
        return await this.api.put(`/groups/${groupId}`, groupData);
    }

    async deleteGroup(groupId) {
        return await this.api.delete(`/groups/${groupId}`);
    }

    async getGroupMembers(groupId) {
        return await this.api.get(`/groups/${groupId}/members`);
    }

    async addMember(groupId, studentId) {
        return await this.api.post(`/groups/${groupId}/members`, { student_id: studentId });
    }

    async removeMember(groupId, studentId) {
        return await this.api.delete(`/groups/${groupId}/members/${studentId}`);
    }

    async getGroupRepositories(groupId) {
        return await this.api.get(`/groups/${groupId}/repositories`);
    }

    async getGroupAnalytics(groupId) {
        return await this.api.get(`/groups/${groupId}/analytics`);
    }

    async getGroupStatistics(groupId) {
        return await this.api.get(`/groups/${groupId}/statistics`);
    }
}

export default GroupService;