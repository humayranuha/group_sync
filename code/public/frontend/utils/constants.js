// Application Constants

// User Roles
export const USER_ROLES = {
    ADMIN: 'admin',
    PROFESSOR: 'professor',
    STUDENT: 'student'
};

// AI Classification Types
export const AI_CLASSIFICATIONS = {
    ACTIVE: 'Active',
    MODERATE: 'Moderate',
    PASSIVE: 'Passive',
    FREE_RIDER: 'Free Rider'
};

// AI Classification Colors
export const AI_CLASSIFICATION_COLORS = {
    [AI_CLASSIFICATIONS.ACTIVE]: 'bg-green-100 text-green-800',
    [AI_CLASSIFICATIONS.MODERATE]: 'bg-yellow-100 text-yellow-800',
    [AI_CLASSIFICATIONS.PASSIVE]: 'bg-orange-100 text-orange-800',
    [AI_CLASSIFICATIONS.FREE_RIDER]: 'bg-red-100 text-red-800'
};

// Risk Levels
export const RISK_LEVELS = {
    LOW: 'Low',
    MEDIUM: 'Medium',
    HIGH: 'High'
};

// Repository Types
export const REPOSITORY_TYPES = {
    ORIGINAL: 'original',
    COLLABORATOR: 'collaborator',
    FORKED: 'forked'
};

// Activity Types
export const ACTIVITY_TYPES = {
    COMMIT: 'commit',
    PULL_REQUEST: 'pull_request',
    FORK: 'fork',
    BRANCH: 'branch',
    CODE_ADDITION: 'code_addition',
    CODE_DELETION: 'code_deletion'
};

// Report Types
export const REPORT_TYPES = {
    STUDENT: 'student',
    GROUP: 'group',
    COURSE: 'course'
};

// Storage Keys
export const STORAGE_KEYS = {
    AUTH_TOKEN: 'groupsync_auth_token',
    USER_DATA: 'groupsync_user_data',
    THEME: 'groupsync_theme',
    SIDEBAR_STATE: 'groupsync_sidebar_state',
    RECENT_COURSES: 'groupsync_recent_courses',
    FILTERS: 'groupsync_filters'
};

// API Endpoints (from config)
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
        SYNC: '/github/repositories/{repoId}/sync'
    },
    ANALYTICS: {
        STUDENT: '/analytics/student/{studentId}',
        GROUP: '/analytics/group/{groupId}',
        REPORTS: '/analytics/reports'
    },
    AI: {
        EVALUATE_STUDENT: '/ai/evaluate/student/{studentId}',
        EVALUATE_GROUP: '/ai/evaluate/group/{groupId}',
        DETECT_FREERIDERS: '/ai/detect/freeriders/{groupId}'
    }
};

// HTTP Status Codes
export const HTTP_STATUS = {
    OK: 200,
    CREATED: 201,
    BAD_REQUEST: 400,
    UNAUTHORIZED: 401,
    FORBIDDEN: 403,
    NOT_FOUND: 404,
    SERVER_ERROR: 500
};

// Pagination defaults
export const PAGINATION = {
    DEFAULT_PAGE: 1,
    DEFAULT_PER_PAGE: 10,
    PER_PAGE_OPTIONS: [10, 25, 50, 100]
};

// Date Formats
export const DATE_FORMATS = {
    DISPLAY_DATE: 'MMM DD, YYYY',
    DISPLAY_DATETIME: 'MMM DD, YYYY HH:mm',
    DISPLAY_TIME: 'HH:mm',
    API_DATE: 'YYYY-MM-DD',
    API_DATETIME: 'YYYY-MM-DD HH:mm:ss'
};

export default {
    USER_ROLES,
    AI_CLASSIFICATIONS,
    AI_CLASSIFICATION_COLORS,
    RISK_LEVELS,
    REPOSITORY_TYPES,
    ACTIVITY_TYPES,
    REPORT_TYPES,
    STORAGE_KEYS,
    API_ENDPOINTS,
    HTTP_STATUS,
    PAGINATION,
    DATE_FORMATS
};