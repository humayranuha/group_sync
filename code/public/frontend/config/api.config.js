// API Endpoints Configuration
export const API_ENDPOINTS = {
    AUTH: {
        LOGIN: '/auth/login',
        REGISTER: '/auth/register',
        LOGOUT: '/auth/logout',
        REFRESH: '/auth/refresh',
        GITHUB_CONNECT: '/auth/github/connect'
    },
    COURSES: {
        BASE: '/courses',
        BY_PROFESSOR: '/courses/professor/{professorId}',
        BY_STUDENT: '/courses/student/{studentId}',
        GROUPS: '/courses/{courseId}/groups'
    },
    GROUPS: {
        BASE: '/groups',
        MEMBERS: '/groups/{groupId}/members',
        REPOSITORIES: '/groups/{groupId}/repositories',
        ANALYTICS: '/groups/{groupId}/analytics'
    },
    GITHUB: {
        CONNECT: '/github/connect',
        REPOSITORIES: '/github/repositories',
        SYNC: '/github/repositories/{repoId}/sync',
        VERIFY: '/github/verify'
    },
    ANALYTICS: {
        STUDENT: '/analytics/student/{studentId}',
        GROUP: '/analytics/group/{groupId}',
        COMMITS: '/analytics/commits/{repositoryId}',
        REPORTS: '/analytics/reports'
    },
    AI: {
        EVALUATE_STUDENT: '/ai/evaluate/student/{studentId}',
        EVALUATE_GROUP: '/ai/evaluate/group/{groupId}',
        DETECT_FREERIDERS: '/ai/detect/freeriders/{groupId}',
        SUGGESTIONS: '/ai/suggestions/{studentId}'
    },
    REPORTS: {
        GENERATE: '/reports/generate',
        DOWNLOAD: '/reports/download/{reportId}'
    }
};

export const HTTP_METHODS = {
    GET: 'GET',
    POST: 'POST',
    PUT: 'PUT',
    DELETE: 'DELETE'
};

export const HTTP_STATUS = {
    OK: 200,
    CREATED: 201,
    BAD_REQUEST: 400,
    UNAUTHORIZED: 401,
    FORBIDDEN: 403,
    NOT_FOUND: 404,
    SERVER_ERROR: 500
};

export default API_ENDPOINTS;