// AI Service
import APIClient from './api.js';

export class AIService {
    constructor() {
        this.api = new APIClient();
    }

    async getStudentEvaluation(studentId, groupId) {
        const params = groupId ? { group_id: groupId } : {};
        return await this.api.get(`/ai/evaluate/student/${studentId}`, { params });
    }

    async getGroupEvaluation(groupId) {
        return await this.api.get(`/ai/evaluate/group/${groupId}`);
    }

    async detectFreeRiders(groupId) {
        return await this.api.get(`/ai/detect/freeriders/${groupId}`);
    }

    async getImprovementSuggestions(studentId) {
        return await this.api.get(`/ai/suggestions/${studentId}`);
    }

    async getParticipationAnalysis(studentId, weeks = 4) {
        return await this.api.get(`/ai/analysis/participation/${studentId}`, {
            params: { weeks }
        });
    }

    async getQualityAnalysis(studentId, groupId) {
        return await this.api.get(`/ai/analysis/quality/${studentId}`, {
            params: { group_id: groupId }
        });
    }

    async getPredictions(groupId) {
        return await this.api.get(`/ai/predict/${groupId}`);
    }

    async getFeedback(studentId) {
        return await this.api.get(`/ai/feedback/${studentId}`);
    }

    async generateReport(groupId) {
        return await this.api.post(`/ai/report/${groupId}`);
    }
}

export default AIService;