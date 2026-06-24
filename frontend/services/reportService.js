// Report Service
import APIClient from './api.js';

export class ReportService {
    constructor() {
        this.api = new APIClient();
    }

    async getReports() {
        return await this.api.get('/reports');
    }

    async getReportById(reportId) {
        return await this.api.get(`/reports/${reportId}`);
    }

    async generateStudentReport(studentId, groupId, weekRange = null) {
        return await this.api.post('/reports/generate', {
            type: 'student',
            student_id: studentId,
            group_id: groupId,
            weeks: weekRange
        });
    }

    async generateGroupReport(groupId) {
        return await this.api.post('/reports/generate', {
            type: 'group',
            group_id: groupId
        });
    }

    async generateCourseReport(courseId) {
        return await this.api.post('/reports/generate', {
            type: 'course',
            course_id: courseId
        });
    }

    async downloadReport(reportId, format = 'pdf') {
        return await this.api.get(`/reports/download/${reportId}`, {
            params: { format }
        });
    }

    async previewReport(reportId) {
        return await this.api.get(`/reports/preview/${reportId}`);
    }

    async shareReport(reportId, emails) {
        return await this.api.post(`/reports/${reportId}/share`, { emails });
    }

    async deleteReport(reportId) {
        return await this.api.delete(`/reports/${reportId}`);
    }

    async getReportHistory(filters = {}) {
        return await this.api.get('/reports/history', { params: filters });
    }

    async scheduleReport(reportConfig) {
        return await this.api.post('/reports/schedule', reportConfig);
    }

    async cancelScheduledReport(scheduleId) {
        return await this.api.delete(`/reports/schedule/${scheduleId}`);
    }
}

export default ReportService;