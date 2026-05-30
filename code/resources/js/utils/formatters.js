// Formatting Utilities

// Format file size
export function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Format duration (minutes to readable)
export function formatDuration(minutes) {
    if (minutes < 60) return `${minutes} min`;
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    if (remainingMinutes === 0) return `${hours} hour${hours > 1 ? 's' : ''}`;
    return `${hours}h ${remainingMinutes}m`;
}

// Format commit count with suffix
export function formatCommitCount(commits) {
    if (commits >= 1000) return `${(commits / 1000).toFixed(1)}k`;
    return commits.toString();
}

// Format contribution percentage with color class
export function getContributionColorClass(percentage) {
    if (percentage >= 30) return 'text-green-600 bg-green-100';
    if (percentage >= 20) return 'text-blue-600 bg-blue-100';
    if (percentage >= 10) return 'text-yellow-600 bg-yellow-100';
    return 'text-red-600 bg-red-100';
}

// Get AI classification class
export function getClassificationClass(classification) {
    const classes = {
        'Active': 'bg-green-100 text-green-800',
        'Moderate': 'bg-yellow-100 text-yellow-800',
        'Passive': 'bg-orange-100 text-orange-800',
        'Free Rider': 'bg-red-100 text-red-800'
    };
    return classes[classification] || 'bg-gray-100 text-gray-800';
}

// Get risk level class
export function getRiskLevelClass(riskLevel) {
    const classes = {
        'Low': 'text-green-600',
        'Medium': 'text-yellow-600',
        'High': 'text-red-600'
    };
    return classes[riskLevel] || 'text-gray-600';
}

// Format GitHub activity summary
export function formatActivitySummary(activities) {
    const summary = {
        totalCommits: 0,
        totalPRs: 0,
        totalForks: 0,
        totalLinesAdded: 0,
        totalLinesDeleted: 0
    };
    
    activities.forEach(activity => {
        switch (activity.type) {
            case 'commit':
                summary.totalCommits += activity.quantity;
                break;
            case 'pull_request':
                summary.totalPRs += activity.quantity;
                break;
            case 'fork':
                summary.totalForks += activity.quantity;
                break;
            case 'code_addition':
                summary.totalLinesAdded += activity.lines_added || activity.quantity;
                break;
            case 'code_deletion':
                summary.totalLinesDeleted += activity.lines_deleted || activity.quantity;
                break;
        }
    });
    
    return summary;
}

// Format chart data for contribution analytics
export function formatChartData(analytics) {
    return {
        labels: analytics.map(a => a.week_number),
        datasets: [
            {
                label: 'Commits',
                data: analytics.map(a => a.total_commits),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)'
            },
            {
                label: 'Lines Added',
                data: analytics.map(a => a.total_lines_added),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)'
            }
        ]
    };
}

// Format team comparison data
export function formatTeamComparisonData(members) {
    return {
        labels: members.map(m => m.name),
        datasets: [
            {
                label: 'Contribution %',
                data: members.map(m => m.contribution_percentage),
                backgroundColor: 'rgba(59, 130, 246, 0.7)'
            }
        ]
    };
}

// Format time range for reports
export function formatTimeRange(startDate, endDate) {
    const start = formatDate(startDate, 'MMM DD');
    const end = formatDate(endDate, 'MMM DD, YYYY');
    return `${start} - ${end}`;
}

export default {
    formatFileSize,
    formatDuration,
    formatCommitCount,
    getContributionColorClass,
    getClassificationClass,
    getRiskLevelClass,
    formatActivitySummary,
    formatChartData,
    formatTeamComparisonData,
    formatTimeRange
};