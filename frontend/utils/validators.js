// Validation Utilities

// Validate email
export function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@([^\s@.,]+\.)+[^\s@.,]{2,}$/;
    return emailRegex.test(email);
}

// Validate password (min 8 chars, at least one uppercase, one lowercase, one number)
export function isValidPassword(password) {
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/;
    return passwordRegex.test(password);
}

// Validate name (only letters, spaces, hyphens)
export function isValidName(name) {
    const nameRegex = /^[a-zA-Z\s\-']+$/;
    return nameRegex.test(name);
}

// Validate phone number
export function isValidPhone(phone) {
    const phoneRegex = /^\+?[\d\s-]{10,}$/;
    return phoneRegex.test(phone);
}

// Validate URL
export function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

// Validate GitHub repository URL
export function isValidGithubRepo(url) {
    const githubRegex = /^(https?:\/\/)?(www\.)?github\.com\/[\w-]+\/[\w-]+(\/)?$/;
    return githubRegex.test(url);
}

// Validate course code (e.g., CS401, SWE320)
export function isValidCourseCode(code) {
    const courseRegex = /^[A-Z]{2,4}\d{3,4}$/;
    return courseRegex.test(code);
}

// Validate student ID
export function isValidStudentId(id) {
    const studentIdRegex = /^[A-Z0-9]{6,12}$/;
    return studentIdRegex.test(id);
}

// Validate required fields
export function isRequired(value) {
    if (value === null || value === undefined) return false;
    if (typeof value === 'string') return value.trim().length > 0;
    if (Array.isArray(value)) return value.length > 0;
    return true;
}

// Validate min length
export function hasMinLength(value, minLength) {
    if (!value) return false;
    return String(value).length >= minLength;
}

// Validate max length
export function hasMaxLength(value, maxLength) {
    if (!value) return true;
    return String(value).length <= maxLength;
}

// Validate number range
export function isInRange(value, min, max) {
    const num = Number(value);
    if (isNaN(num)) return false;
    return num >= min && num <= max;
}

// Validate is number
export function isNumber(value) {
    return !isNaN(Number(value));
}

// Validate confirm password matches
export function doPasswordsMatch(password, confirmPassword) {
    return password === confirmPassword;
}

// Validate form data
export function validateForm(formData, rules) {
    const errors = {};
    
    for (const [field, fieldRules] of Object.entries(rules)) {
        const value = formData[field];
        
        for (const rule of fieldRules) {
            let isValid = true;
            let errorMessage = '';
            
            switch (rule.type) {
                case 'required':
                    isValid = isRequired(value);
                    errorMessage = rule.message || `${field} is required`;
                    break;
                case 'email':
                    isValid = isValidEmail(value);
                    errorMessage = rule.message || `Invalid email format`;
                    break;
                case 'password':
                    isValid = isValidPassword(value);
                    errorMessage = rule.message || `Password must be at least 8 characters with uppercase, lowercase, and number`;
                    break;
                case 'minLength':
                    isValid = hasMinLength(value, rule.value);
                    errorMessage = rule.message || `${field} must be at least ${rule.value} characters`;
                    break;
                case 'maxLength':
                    isValid = hasMaxLength(value, rule.value);
                    errorMessage = rule.message || `${field} must be at most ${rule.value} characters`;
                    break;
                case 'confirm':
                    isValid = doPasswordsMatch(value, formData[rule.matchField]);
                    errorMessage = rule.message || `Passwords do not match`;
                    break;
                case 'url':
                    isValid = isValidUrl(value);
                    errorMessage = rule.message || `Invalid URL format`;
                    break;
                case 'github':
                    isValid = isValidGithubRepo(value);
                    errorMessage = rule.message || `Invalid GitHub repository URL`;
                    break;
            }
            
            if (!isValid) {
                errors[field] = errorMessage;
                break;
            }
        }
    }
    
    return {
        isValid: Object.keys(errors).length === 0,
        errors
    };
}

// Sanitize input (prevent XSS)
export function sanitizeInput(input) {
    if (!input) return '';
    const div = document.createElement('div');
    div.textContent = input;
    return div.innerHTML;
}

// Escape HTML
export function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

export default {
    isValidEmail,
    isValidPassword,
    isValidName,
    isValidPhone,
    isValidUrl,
    isValidGithubRepo,
    isValidCourseCode,
    isValidStudentId,
    isRequired,
    hasMinLength,
    hasMaxLength,
    isInRange,
    isNumber,
    doPasswordsMatch,
    validateForm,
    sanitizeInput,
    escapeHtml
};