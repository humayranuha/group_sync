// Analytics Service
import APIClient from './api.js';

export class AnalyticsService {
    constructor() {
        this.api = new APIClient();
    }

    async getStudentAnalytics(studentId, groupId, weekRange = null) {
        const params = weekRange ? { weeks: weekRange } : {};
        if (groupId) params.group_id = groupId;
        return await this.api.get(`/analytics/student/${studentId}`, { params });
    }

    async getGroupAnalytics(groupId, timeframe = 'week') {
        return await this.api.get(`/analytics/group/${groupId}`, {
            params: { timeframe }
        });
    }

    async getCourseAnalytics(courseId) {
        return await this.api.get(`/analytics/course/${courseId}`);
    }

    async getContributionMetrics(groupId, timeframe = 'week') {
        return await this.api.get(`/analytics/group/${groupId}/contributions`, {
            params: { timeframe }
        });
    }

    async getCommitTrends(repositoryId, days = 30) {
        return await this.api.get(`/analytics/repository/${repositoryId}/commits`, {
            params: { days }
        });
    }

    async getActivityHeatmap(studentId, month = null) {
        const params = month ? { month } : {};
        return await this.api.get(`/analytics/student/${studentId}/heatmap`, { params });
    }

    async getTeamComparison(groupId) {
        return await this.api.get(`/analytics/group/${groupId}/comparison`);
    }

    async getActivityConsistency(studentId, weeks = 4) {
        return await this.api.get(`/analytics/student/${studentId}/consistency`, {
            params: { weeks }
        });
    }

    async getWeeklyActivity(groupId) {
        return await this.api.get(`/analytics/group/${groupId}/weekly-activity`);
    }

    async generateReport(reportType, filters) {
        return await this.api.post('/analytics/reports/generate', {
            type: reportType,
            filters
        });
    }

    async exportAnalytics(format = 'csv', data) {
        return await this.api.post('/analytics/export', {
            format,
            data
        });
    }
}

export default AnalyticsService;