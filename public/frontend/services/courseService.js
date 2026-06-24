// Course Service
import APIClient from './api.js';

export class CourseService {
    constructor() {
        this.api = new APIClient();
    }

    async getCourses() {
        return await this.api.get('/courses');
    }

    async getCourseById(courseId) {
        return await this.api.get(`/courses/${courseId}`);
    }

    async getCoursesByProfessor(professorId) {
        return await this.api.get(`/courses/professor/${professorId}`);
    }

    async getCoursesByStudent(studentId) {
        return await this.api.get(`/courses/student/${studentId}`);
    }

    async createCourse(courseData) {
        return await this.api.post('/courses', courseData);
    }

    async updateCourse(courseId, courseData) {
        return await this.api.put(`/courses/${courseId}`, courseData);
    }

    async deleteCourse(courseId) {
        return await this.api.delete(`/courses/${courseId}`);
    }

    async getCourseGroups(courseId) {
        return await this.api.get(`/courses/${courseId}/groups`);
    }

    async getCourseStudents(courseId) {
        return await this.api.get(`/courses/${courseId}/students`);
    }

    async getCourseStatistics(courseId) {
        return await this.api.get(`/courses/${courseId}/statistics`);
    }

    async enrollStudent(courseId, studentId) {
        return await this.api.post(`/courses/${courseId}/enroll`, { student_id: studentId });
    }

    async removeStudent(courseId, studentId) {
        return await this.api.delete(`/courses/${courseId}/students/${studentId}`);
    }
}

export default CourseService;