// PDF Generation Utilities

// Generate student report PDF
export async function generateStudentReport(studentData, analytics, aiFeedback) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Add header
    doc.setFontSize(20);
    doc.setTextColor(41, 128, 185);
    doc.text('GroupSync - Student Performance Report', 20, 20);
    
    doc.setFontSize(10);
    doc.setTextColor(100, 100, 100);
    doc.text(`Generated: ${new Date().toLocaleDateString()}`, 20, 30);
    doc.line(20, 35, 190, 35);
    
    // Student info
    doc.setFontSize(14);
    doc.setTextColor(0, 0, 0);
    doc.text('Student Information', 20, 50);
    
    doc.setFontSize(10);
    doc.text(`Name: ${studentData.first_name} ${studentData.last_name}`, 20, 65);
    doc.text(`Student ID: ${studentData.student_id || 'N/A'}`, 20, 75);
    doc.text(`Course: ${studentData.course_name || 'N/A'}`, 20, 85);
    doc.text(`Group: ${studentData.group_name || 'N/A'}`, 20, 95);
    
    // Contribution metrics
    doc.setFontSize(14);
    doc.text('Contribution Metrics', 20, 115);
    
    doc.setFontSize(10);
    let yPos = 130;
    const metrics = [
        `Total Commits: ${analytics.total_commits || 0}`,
        `Pull Requests: ${analytics.total_prs || 0}`,
        `Lines Added: ${(analytics.total_lines_added || 0).toLocaleString()}`,
        `Lines Deleted: ${(analytics.total_lines_deleted || 0).toLocaleString()}`,
        `Contribution Percentage: ${analytics.contribution_percentage || 0}%`,
        `Activity Score: ${analytics.activity_consistency_score || 0}/100`
    ];
    
    metrics.forEach((metric, index) => {
        const col = index < 3 ? 20 : 110;
        const row = yPos + (index % 3) * 10;
        doc.text(metric, col, row);
    });
    
    // AI Evaluation
    doc.addPage();
    doc.setFontSize(16);
    doc.setTextColor(41, 128, 185);
    doc.text('AI-Powered Evaluation', 20, 20);
    
    doc.setFontSize(12);
    doc.setTextColor(0, 0, 0);
    doc.text(`Classification: ${aiFeedback.classification || 'N/A'}`, 20, 40);
    doc.text(`Risk Level: ${aiFeedback.risk_level || 'N/A'}`, 20, 50);
    
    doc.setFontSize(11);
    doc.text('Feedback:', 20, 70);
    const feedbackLines = doc.splitTextToSize(aiFeedback.feedback || 'No feedback available', 170);
    doc.text(feedbackLines, 20, 80);
    
    if (aiFeedback.suggestions && aiFeedback.suggestions.length) {
        doc.text('Improvement Suggestions:', 20, 120);
        aiFeedback.suggestions.forEach((suggestion, index) => {
            doc.text(`• ${suggestion}`, 25, 130 + (index * 10));
        });
    }
    
    // Footer
    const pageCount = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(150, 150, 150);
        doc.text(
            `GroupSync - Academic Collaboration Analytics | Page ${i} of ${pageCount}`,
            20,
            doc.internal.pageSize.height - 10
        );
    }
    
    // Save PDF
    doc.save(`student_report_${studentData.id || Date.now()}.pdf`);
}

// Generate group report PDF
export async function generateGroupReport(groupData, members, metrics) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('landscape');
    
    // Header
    doc.setFontSize(20);
    doc.setTextColor(41, 128, 185);
    doc.text('GroupSync - Group Performance Report', 20, 20);
    
    doc.setFontSize(10);
    doc.setTextColor(100, 100, 100);
    doc.text(`Group: ${groupData.name}`, 20, 30);
    doc.text(`Course: ${groupData.course_name || 'N/A'}`, 20, 38);
    doc.text(`Generated: ${new Date().toLocaleDateString()}`, 20, 46);
    doc.line(20, 50, 270, 50);
    
    // Team summary
    doc.setFontSize(14);
    doc.setTextColor(0, 0, 0);
    doc.text('Team Summary', 20, 65);
    
    doc.setFontSize(10);
    doc.text(`Total Members: ${members.length}`, 20, 80);
    doc.text(`Total Commits: ${metrics.total_commits || 0}`, 20, 90);
    doc.text(`Total PRs: ${metrics.total_prs || 0}`, 20, 100);
    doc.text(`Average Activity Score: ${metrics.avg_activity_score || 0}/100`, 20, 110);
    
    // Members table
    doc.setFontSize(14);
    doc.text('Team Members', 20, 130);
    
    // Table headers
    doc.setFillColor(230, 240, 255);
    doc.rect(20, 140, 230, 8, 'F');
    doc.setFontSize(9);
    doc.text('Name', 25, 147);
    doc.text('Commits', 95, 147);
    doc.text('PRs', 135, 147);
    doc.text('Lines Added', 165, 147);
    doc.text('Classification', 210, 147);
    
    // Table rows
    let y = 155;
    members.forEach((member, index) => {
        if (y > 190) {
            doc.addPage();
            y = 20;
        }
        doc.text(member.name || `Member ${index + 1}`, 25, y);
        doc.text((member.total_commits || 0).toString(), 95, y);
        doc.text((member.total_prs || 0).toString(), 135, y);
        doc.text((member.lines_added || 0).toLocaleString(), 165, y);
        doc.text(member.classification || 'N/A', 210, y);
        y += 10;
    });
    
    // Footer
    const pageCount = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(150, 150, 150);
        doc.text(
            `GroupSync - Academic Collaboration Analytics | Page ${i} of ${pageCount}`,
            20,
            doc.internal.pageSize.height - 10
        );
    }
    
    doc.save(`group_report_${groupData.id || Date.now()}.pdf`);
}

// Generate course report PDF
export async function generateCourseReport(courseData, groups, students) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Header
    doc.setFontSize(20);
    doc.setTextColor(41, 128, 185);
    doc.text('GroupSync - Course Performance Report', 20, 20);
    
    doc.setFontSize(10);
    doc.setTextColor(100, 100, 100);
    doc.text(`Course: ${courseData.name}`, 20, 30);
    doc.text(`Code: ${courseData.code || 'N/A'}`, 20, 38);
    doc.text(`Semester: ${courseData.semester || 'N/A'}`, 20, 46);
    doc.text(`Generated: ${new Date().toLocaleDateString()}`, 20, 54);
    doc.line(20, 58, 190, 58);
    
    // Statistics
    doc.setFontSize(14);
    doc.setTextColor(0, 0, 0);
    doc.text('Course Statistics', 20, 75);
    
    doc.setFontSize(10);
    doc.text(`Total Groups: ${groups.length}`, 20, 90);
    doc.text(`Total Students: ${students.length}`, 20, 100);
    
    const activeStudents = students.filter(s => s.classification === 'Active').length;
    const freeRiders = students.filter(s => s.classification === 'Free Rider').length;
    
    doc.text(`Active Students: ${activeStudents}`, 20, 110);
    doc.text(`Free Riders Detected: ${freeRiders}`, 20, 120);
    
    doc.save(`course_report_${courseData.id || Date.now()}.pdf`);
}

export default {
    generateStudentReport,
    generateGroupReport,
    generateCourseReport
};