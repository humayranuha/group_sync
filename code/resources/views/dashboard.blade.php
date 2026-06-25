<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Professor Dashboard — GroupSync</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800;14..32,900&display=swap" rel="stylesheet" />
    <style>
        * { font-family: 'Inter', system-ui, sans-serif; }
        body { background: #f6f9fc; }
        .dark body { background: #0b1717; color: #e8edf0; }
        .btn-teal {
            background: #3AAFA9; color: #fff; transition: all 0.3s ease;
            padding: 0.6rem 1.5rem; border-radius: 12px; font-weight: 600; border: none; cursor: pointer;
        }
        .btn-teal:hover { background: #2B7A78; transform: translateY(-2px); }
        .btn-teal-outline {
            background: transparent; border: 2px solid #3AAFA9; color: #3AAFA9;
            transition: all 0.3s ease; padding: 0.6rem 1.5rem; border-radius: 12px; font-weight: 600; cursor: pointer;
        }
        .btn-teal-outline:hover { background: #3AAFA9; color: #fff; }
        .card-glass {
            background: rgba(255,255,255,0.80);
            backdrop-filter: blur(4px);
            border: 1px solid rgba(58,175,169,0.06);
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.4s cubic-bezier(0.22,1,0.36,1);
        }
        .dark .card-glass { background: rgba(19,36,36,0.80); }
        .stat-card {
            transition: all 0.4s cubic-bezier(0.22,1,0.36,1);
            border: 1px solid rgba(58,175,169,0.06);
            background: rgba(255,255,255,0.80);
            backdrop-filter: blur(4px);
            border-radius: 20px;
            padding: 1.5rem;
            cursor: pointer;
        }
        .dark .stat-card { background: rgba(19,36,36,0.80); }
        .input-teal {
            background: #ffffff;
            border: 1px solid #d1d5db;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            width: 100%;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        .input-teal:focus {
            border-color: #3AAFA9;
            box-shadow: 0 0 0 3px rgba(58,175,169,0.20);
            outline: none;
        }
        .dark .input-teal { background: #1e293b; border-color: rgba(255,255,255,0.08); color: #e2e8f0; }
        .textarea-teal {
            background: #ffffff;
            border: 1px solid #d1d5db;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            width: 100%;
            font-size: 0.95rem;
            resize: vertical;
            min-height: 80px;
            transition: all 0.3s ease;
        }
        .textarea-teal:focus {
            border-color: #3AAFA9;
            box-shadow: 0 0 0 3px rgba(58,175,169,0.20);
            outline: none;
        }
        .dark .textarea-teal { background: #1e293b; border-color: rgba(255,255,255,0.08); color: #e2e8f0; }
        .toast {
            position: fixed; bottom: 20px; right: 20px; z-index: 9999;
            padding: 12px 24px; border-radius: 12px; color: white;
            animation: slideIn 0.3s ease;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .status-online { color: #22c55e; }
        .status-offline { color: #ef4444; }
        .status-checking { color: #f59e0b; }
        .badge-active { background: rgba(58,175,169,0.15); color: #3AAFA9; padding: 2px 10px; border-radius: 999px; font-size: 0.75rem; }
        .badge-moderate { background: rgba(58,175,169,0.10); color: #7ccfca; padding: 2px 10px; border-radius: 999px; font-size: 0.75rem; }
        .badge-passive { background: rgba(58,175,169,0.08); color: #94a3b8; padding: 2px 10px; border-radius: 999px; font-size: 0.75rem; }
        .badge-free-rider { background: rgba(58,175,169,0.06); color: #64748b; padding: 2px 10px; border-radius: 999px; font-size: 0.75rem; }
        
        .dark .badge-active { background: rgba(58,175,169,0.15); color: #5ccbc5; }
        .dark .badge-moderate { background: rgba(58,175,169,0.10); color: #7ccfca; }
        .dark .badge-passive { background: rgba(58,175,169,0.06); color: #94a3b8; }
        .dark .badge-free-rider { background: rgba(58,175,169,0.04); color: #64748b; }
        
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.6);
            display: none; align-items: center; justify-content: center;
            z-index: 9999; backdrop-filter: blur(4px);
        }
        .modal-overlay.active { display: flex; }
        .modal-box {
            background: white; border-radius: 24px; max-width: 800px; width: 95%;
            max-height: 85vh; overflow-y: auto; padding: 30px;
            position: relative; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .dark .modal-box { background: #132424; }
        .modal-close {
            position: absolute; top: 16px; right: 20px; font-size: 28px;
            cursor: pointer; color: #999; transition: color 0.2s;
        }
        .modal-close:hover { color: #333; }
        .dark .modal-close { color: #666; }
        .dark .modal-close:hover { color: #ccc; }
    </style>
</head>
<body>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">
                    📚 GroupSync Professor Dashboard
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Manage your courses, students, and communications</p>
            </div>
            <button onclick="toggleDarkMode()" class="btn-teal-outline text-sm py-2 px-4">
                <i class="fas fa-moon"></i> Toggle Theme
            </button>
        </div>

        <!-- Server Status -->
        <div class="card-glass mb-6">
            <div class="flex flex-wrap items-center gap-4">
                <span class="text-lg">🔌</span>
                <span class="font-medium">Node.js Server:</span>
                <span id="serverStatus" class="text-sm font-semibold">
                    <span class="status-checking">⏳ Checking...</span>
                </span>
                <button onclick="checkNodeServer()" class="btn-teal text-sm py-1 px-4">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <span class="text-xs text-gray-400 ml-auto" id="lastCheckTime"></span>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="stat-card text-center" onclick="loadStudents()">
                <p class="text-2xl font-bold text-[#3AAFA9]" id="totalStudents">0</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Students</p>
            </div>
            <div class="stat-card text-center" onclick="loadCourses()">
                <p class="text-2xl font-bold text-[#3AAFA9]" id="totalCourses">0</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Courses</p>
            </div>
            <div class="stat-card text-center" onclick="loadGroups()">
                <p class="text-2xl font-bold text-[#3AAFA9]" id="totalGroups">0</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Groups</p>
            </div>
            <div class="stat-card text-center" onclick="loadProjects()">
                <p class="text-2xl font-bold text-[#3AAFA9]" id="totalProjects">0</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Projects</p>
            </div>
        </div>

        <!-- Email Test Section -->
        <div class="card-glass mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">📧 Send Email to Student</h2>
            <form id="emailTestForm" onsubmit="sendTestEmail(event)" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Student Email *</label>
                        <input type="email" id="testEmail" placeholder="student@example.com" class="input-teal mt-1" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Student Name</label>
                        <input type="text" id="testStudentName" placeholder="John Doe" class="input-teal mt-1">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Subject *</label>
                    <input type="text" id="testSubject" placeholder="Email Subject" class="input-teal mt-1" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Message *</label>
                    <textarea id="testMessage" rows="3" placeholder="Write your message here..." class="textarea-teal mt-1" required></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn-teal">
                        <i class="fas fa-paper-plane"></i> Send Email
                    </button>
                    <button type="button" onclick="clearEmailForm()" class="btn-teal-outline">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </form>
            <div id="emailResult" class="mt-4 text-sm"></div>
        </div>

        <!-- Feedback Test Section -->
        <div class="card-glass mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">💬 Send Feedback to Student</h2>
            <form id="feedbackTestForm" onsubmit="sendTestFeedback(event)" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Student Email *</label>
                        <input type="email" id="feedbackEmail" placeholder="student@example.com" class="input-teal mt-1" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Student Name</label>
                        <input type="text" id="feedbackStudentName" placeholder="John Doe" class="input-teal mt-1">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Feedback Type</label>
                    <select id="feedbackType" class="input-teal mt-1">
                        <option value="positive">👍 Positive</option>
                        <option value="constructive">💡 Constructive</option>
                        <option value="critical">⚠️ Critical</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Feedback Message *</label>
                    <textarea id="feedbackMessage" rows="3" placeholder="Write your feedback here..." class="textarea-teal mt-1" required></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="btn-teal">
                        <i class="fas fa-paper-plane"></i> Send Feedback
                    </button>
                    <button type="button" onclick="clearFeedbackForm()" class="btn-teal-outline">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </form>
            <div id="feedbackResult" class="mt-4 text-sm"></div>
        </div>

        <!-- Student List (Demo) -->
        <div class="card-glass">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">👨‍🎓 Students</h2>
                <button onclick="loadStudents()" class="btn-teal text-sm py-1 px-3">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div id="studentList" class="space-y-2">
                <p class="text-gray-500 dark:text-gray-400 text-sm">No students loaded. Click refresh to load.</p>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div id="studentModal" class="modal-overlay" onclick="if(event.target===this) closeModal()">
        <div class="modal-box">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-4" id="modalTitle">Student Details</h2>
            <div id="modalContent"></div>
        </div>
    </div>

    <script>
        // ============================================
        // CONFIGURATION
        // ============================================
        const LARAVEL_API = '{{ url('/api') }}';
        const NODE_API = 'http://localhost:5000';

        // ============================================
        // TOAST SYSTEM
        // ============================================
        function showToast(message, type = 'success') {
            const colors = {
                success: 'bg-green-600',
                error: 'bg-red-600',
                warning: 'bg-yellow-600',
                info: 'bg-blue-600'
            };
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            const toast = document.createElement('div');
            toast.className = `toast ${colors[type] || colors.info}`;
            toast.innerHTML = `<span>${icons[type] || ''}</span> ${message}`;
            document.body.appendChild(toast);
            setTimeout(() => { 
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        // ============================================
        // DARK MODE
        // ============================================
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            const isDark = document.documentElement.classList.contains('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            showToast(isDark ? '🌙 Dark mode enabled' : '☀️ Light mode enabled', 'info');
        }

        // Load theme on startup
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }

        // ============================================
        // CHECK NODE.JS SERVER STATUS
        // ============================================
        async function checkNodeServer() {
            const statusEl = document.getElementById('serverStatus');
            const timeEl = document.getElementById('lastCheckTime');
            statusEl.innerHTML = '<span class="status-checking">⏳ Checking...</span>';

            try {
                // Direct check (Node.js)
                const nodeResponse = await fetch(`${NODE_API}/api/health`);
                const nodeData = await nodeResponse.json();

                if (nodeResponse.ok) {
                    statusEl.innerHTML = `<span class="status-online">✅ Online</span> (${nodeData.message})`;
                    timeEl.textContent = `Last checked: ${new Date().toLocaleTimeString()}`;
                    showToast('✅ Node.js server is running!', 'success');
                    return true;
                }
            } catch (error) {
                // Try through Laravel
                try {
                    const laravelResponse = await fetch(`${LARAVEL_API}/node-status`);
                    const laravelData = await laravelResponse.json();

                    if (laravelResponse.ok && laravelData.success) {
                        statusEl.innerHTML = `<span class="status-online">✅ Online</span> (via Laravel)`;
                        timeEl.textContent = `Last checked: ${new Date().toLocaleTimeString()}`;
                        showToast('✅ Node.js server is running!', 'success');
                        return true;
                    }
                } catch (e) {
                    statusEl.innerHTML = '<span class="status-offline">❌ Offline</span>';
                    timeEl.textContent = `Last checked: ${new Date().toLocaleTimeString()}`;
                    showToast('❌ Node.js server is not running!', 'error');
                    return false;
                }
            }

            statusEl.innerHTML = '<span class="status-offline">❌ Offline</span>';
            timeEl.textContent = `Last checked: ${new Date().toLocaleTimeString()}`;
            showToast('❌ Node.js server is not running!', 'error');
            return false;
        }

        // ============================================
        // SEND TEST EMAIL
        // ============================================
        async function sendTestEmail(e) {
            e.preventDefault();
            const email = document.getElementById('testEmail').value;
            const studentName = document.getElementById('testStudentName').value;
            const subject = document.getElementById('testSubject').value;
            const message = document.getElementById('testMessage').value;
            const resultEl = document.getElementById('emailResult');

            if (!email || !subject || !message) {
                resultEl.innerHTML = '<span class="text-red-600">❌ Please fill all required fields</span>';
                return;
            }

            resultEl.innerHTML = '<span class="text-yellow-600">⏳ Sending email...</span>';

            try {
                // Try Node.js directly first
                const response = await fetch(`${NODE_API}/api/send-email`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ to: email, subject, body: message, studentName })
                });

                const data = await response.json();

                if (data.success) {
                    resultEl.innerHTML = `<span class="text-green-600">✅ Email sent successfully!</span>`;
                    showToast('✅ Email sent successfully!', 'success');
                    
                    if (data.testMode) {
                        resultEl.innerHTML += `<br><span class="text-yellow-500">⚠️ Test mode: Email saved to log (no real email sent)</span>`;
                        showToast('⚠️ Test mode - check logs folder', 'warning');
                    }
                    
                    // Clear form after success
                    document.getElementById('testSubject').value = '';
                    document.getElementById('testMessage').value = '';
                } else {
                    resultEl.innerHTML = `<span class="text-red-600">❌ Failed: ${data.message}</span>`;
                    showToast('❌ Failed to send email', 'error');
                }
            } catch (error) {
                // Try through Laravel
                try {
                    const laravelResponse = await fetch(`${LARAVEL_API}/node-send-email`, {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ email, subject, message, student_name: studentName })
                    });

                    const laravelData = await laravelResponse.json();

                    if (laravelResponse.ok && laravelData.success) {
                        resultEl.innerHTML = `<span class="text-green-600">✅ Email sent via Laravel!</span>`;
                        showToast('✅ Email sent successfully!', 'success');
                        document.getElementById('testSubject').value = '';
                        document.getElementById('testMessage').value = '';
                    } else {
                        resultEl.innerHTML = `<span class="text-red-600">❌ Failed: ${laravelData.message || 'Unknown error'}</span>`;
                        showToast('❌ Failed to send email', 'error');
                    }
                } catch (e) {
                    resultEl.innerHTML = `<span class="text-red-600">❌ Error: Server not reachable. Make sure Node.js server is running.</span>`;
                    showToast('❌ Server not reachable', 'error');
                }
            }
        }

        // ============================================
        // SEND TEST FEEDBACK
        // ============================================
        async function sendTestFeedback(e) {
            e.preventDefault();
            const email = document.getElementById('feedbackEmail').value;
            const studentName = document.getElementById('feedbackStudentName').value;
            const feedbackType = document.getElementById('feedbackType').value;
            const message = document.getElementById('feedbackMessage').value;
            const resultEl = document.getElementById('feedbackResult');

            if (!email || !message) {
                resultEl.innerHTML = '<span class="text-red-600">❌ Please fill all required fields</span>';
                return;
            }

            resultEl.innerHTML = '<span class="text-yellow-600">⏳ Sending feedback...</span>';

            try {
                const response = await fetch(`${NODE_API}/api/send-feedback`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        studentEmail: email,
                        studentName,
                        feedbackType,
                        feedbackMessage: message
                    })
                });

                const data = await response.json();

                if (data.success) {
                    resultEl.innerHTML = `<span class="text-green-600">✅ Feedback email sent!</span>`;
                    showToast('✅ Feedback sent successfully!', 'success');
                    document.getElementById('feedbackMessage').value = '';
                } else {
                    resultEl.innerHTML = `<span class="text-red-600">❌ Failed: ${data.message}</span>`;
                    showToast('❌ Failed to send feedback', 'error');
                }
            } catch (error) {
                // Try through Laravel
                try {
                    const laravelResponse = await fetch(`${LARAVEL_API}/node-send-feedback`, {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            email,
                            student_name: studentName,
                            feedback_type: feedbackType,
                            message
                        })
                    });

                    const laravelData = await laravelResponse.json();

                    if (laravelResponse.ok && laravelData.success) {
                        resultEl.innerHTML = `<span class="text-green-600">✅ Feedback sent via Laravel!</span>`;
                        showToast('✅ Feedback sent successfully!', 'success');
                        document.getElementById('feedbackMessage').value = '';
                    } else {
                        resultEl.innerHTML = `<span class="text-red-600">❌ Failed: ${laravelData.message || 'Unknown error'}</span>`;
                        showToast('❌ Failed to send feedback', 'error');
                    }
                } catch (e) {
                    resultEl.innerHTML = `<span class="text-red-600">❌ Error: Server not reachable.</span>`;
                    showToast('❌ Server not reachable', 'error');
                }
            }
        }

        // ============================================
        // CLEAR FORMS
        // ============================================
        function clearEmailForm() {
            document.getElementById('testEmail').value = '';
            document.getElementById('testStudentName').value = '';
            document.getElementById('testSubject').value = '';
            document.getElementById('testMessage').value = '';
            document.getElementById('emailResult').innerHTML = '';
        }

        function clearFeedbackForm() {
            document.getElementById('feedbackEmail').value = '';
            document.getElementById('feedbackStudentName').value = '';
            document.getElementById('feedbackMessage').value = '';
            document.getElementById('feedbackResult').innerHTML = '';
        }

        // ============================================
        // LOAD STUDENTS (Demo Data)
        // ============================================
        function loadStudents() {
            const listEl = document.getElementById('studentList');
            listEl.innerHTML = '<p class="text-gray-500 text-sm">Loading students...</p>';

            // Demo student data
            const students = [
                { id: 1, name: 'John Doe', email: 'john@example.com', classification: 'Active', score: 85 },
                { id: 2, name: 'Jane Smith', email: 'jane@example.com', classification: 'Moderate', score: 70 },
                { id: 3, name: 'Mike Brown', email: 'mike@example.com', classification: 'Passive', score: 45 },
                { id: 4, name: 'Lisa Wong', email: 'lisa@example.com', classification: 'Free Rider', score: 25 }
            ];

            document.getElementById('totalStudents').textContent = students.length;

            listEl.innerHTML = students.map(s => `
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700/50 transition cursor-pointer" onclick="showStudentDetails(${s.id})">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[#3AAFA9] flex items-center justify-center text-white font-bold text-sm">
                            ${s.name.split(' ').map(n => n[0]).join('')}
                        </div>
                        <div>
                            <p class="font-medium text-gray-800 dark:text-gray-100">${s.name}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">${s.email}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="badge-${s.classification.toLowerCase().replace(' ', '-')}">${s.classification}</span>
                        <span class="text-sm font-semibold ${s.score >= 70 ? 'text-green-600' : s.score >= 50 ? 'text-yellow-600' : 'text-red-600'}">${s.score}%</span>
                    </div>
                </div>
            `).join('');
        }

        // ============================================
        // LOAD COURSES (Demo Data)
        // ============================================
        function loadCourses() {
            const courses = [
                { id: 1, code: 'CSE3104', title: 'Software Engineering', semester: 'Spring 2026', students: 32 },
                { id: 2, code: 'CSE3105', title: 'Database Systems', semester: 'Spring 2026', students: 28 },
                { id: 3, code: 'CSE3106', title: 'Web Development', semester: 'Spring 2026', students: 25 }
            ];
            document.getElementById('totalCourses').textContent = courses.length;
            showToast(`📚 ${courses.length} courses loaded`, 'info');
        }

        function loadGroups() {
            document.getElementById('totalGroups').textContent = '6';
            showToast('👥 Groups loaded', 'info');
        }

        function loadProjects() {
            document.getElementById('totalProjects').textContent = '4';
            showToast('📋 Projects loaded', 'info');
        }

        // ============================================
        // STUDENT DETAILS MODAL
        // ============================================
        function showStudentDetails(id) {
            const students = [
                { id: 1, name: 'John Doe', email: 'john@example.com', classification: 'Active', score: 85, github: 'johndoe' },
                { id: 2, name: 'Jane Smith', email: 'jane@example.com', classification: 'Moderate', score: 70, github: 'janesmith' },
                { id: 3, name: 'Mike Brown', email: 'mike@example.com', classification: 'Passive', score: 45, github: 'mikebrown' },
                { id: 4, name: 'Lisa Wong', email: 'lisa@example.com', classification: 'Free Rider', score: 25, github: 'lisawong' }
            ];

            const student = students.find(s => s.id === id);
            if (!student) return;

            document.getElementById('modalTitle').textContent = `👤 ${student.name}`;
            document.getElementById('modalContent').innerHTML = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg text-center">
                            <p class="text-2xl font-bold text-blue-600">${student.email}</p>
                            <p class="text-xs text-gray-500">Email</p>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg text-center">
                            <p class="text-2xl font-bold text-green-600">${student.classification}</p>
                            <p class="text-xs text-gray-500">Classification</p>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 p-3 rounded-lg text-center">
                            <p class="text-2xl font-bold text-purple-600">${student.score}%</p>
                            <p class="text-xs text-gray-500">Activity Score</p>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-lg text-center">
                            <p class="text-2xl font-bold text-yellow-600">@${student.github}</p>
                            <p class="text-xs text-gray-500">GitHub</p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-3">
                        <button onclick="sendEmailToStudent('${student.email}', '${student.name}')" class="btn-teal flex-1 text-sm">
                            <i class="fas fa-envelope"></i> Send Email
                        </button>
                        <button onclick="sendFeedbackToStudent('${student.email}', '${student.name}')" class="btn-teal-outline flex-1 text-sm">
                            <i class="fas fa-comment"></i> Send Feedback
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('studentModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('studentModal').classList.remove('active');
        }

        function sendEmailToStudent(email, name) {
            document.getElementById('testEmail').value = email;
            document.getElementById('testStudentName').value = name;
            document.getElementById('testSubject').value = `Hello ${name},`;
            document.getElementById('testMessage').value = `Dear ${name},\n\nI hope you are doing well. Please check your progress in the course.\n\nBest regards,\nProfessor`;
            closeModal();
            document.getElementById('emailTestForm').scrollIntoView({ behavior: 'smooth' });
            showToast(`📧 Email form filled for ${name}`, 'info');
        }

        function sendFeedbackToStudent(email, name) {
            document.getElementById('feedbackEmail').value = email;
            document.getElementById('feedbackStudentName').value = name;
            document.getElementById('feedbackMessage').value = `Dear ${name},\n\nHere is some feedback on your performance...\n\nBest regards,\nProfessor`;
            closeModal();
            document.getElementById('feedbackTestForm').scrollIntoView({ behavior: 'smooth' });
            showToast(`💬 Feedback form filled for ${name}`, 'info');
        }

        // ============================================
        // INIT
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            // Check server status
            setTimeout(checkNodeServer, 1000);
            
            // Load students
            setTimeout(loadStudents, 500);
            
            // Auto-refresh server status every 30 seconds
            setInterval(checkNodeServer, 30000);
        });
    </script>

</body>
</html>