<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GroupSync - Academic Collaboration Analytics Platform</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Tailwind CSS CDN - No compilation needed! -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <!-- Custom CSS (replace your @apply styles) -->
    <style>
        /* Base styles replacing Tailwind @apply */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        /* Animation */
        .animate-spin { animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* Card hover */
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        
        /* Sidebar */
        .sidebar-transition { transition: transform 0.3s ease-in-out; }
        
        /* Navigation active state */
        .nav-link.active { background-color: #eff6ff; color: #1d4ed8; }
        
        /* Dashboard stats grid */
        .dashboard-stats-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.5rem;
        }
        @media (min-width: 640px) {
            .dashboard-stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            .dashboard-stats-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }
        
        /* Chart container */
        .chart-container { background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); padding: 1.5rem; }
        .chart-title { font-size: 1.125rem; font-weight: 600; color: #1f2937; margin-bottom: 1rem; }
        
        /* Activity indicators */
        .activity-indicator { display: inline-flex; align-items: center; }
        .activity-dot { width: 0.5rem; height: 0.5rem; border-radius: 9999px; margin-right: 0.5rem; }
        .activity-dot-active { background-color: #22c55e; }
        .activity-dot-moderate { background-color: #eab308; }
        .activity-dot-passive { background-color: #f97316; }
        .activity-dot-freerider { background-color: #ef4444; }
        
        /* GitHub repo card */
        .github-repo-card { border: 1px solid #e5e7eb; border-radius: 0.5rem; padding: 1rem; transition: all 0.2s; }
        .github-repo-card:hover { box-shadow: 0 1px 3px 0 rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-gray-50">
    <div id="app">
        <div class="flex items-center justify-center min-h-screen">
            <div class="text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
                <p class="text-gray-600">Loading GroupSync...</p>
            </div>
        </div>
    </div>

    <script>
        // Laravel configuration for JavaScript
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}',
            user: @json(auth()->user()),
            isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
            apiBaseUrl: '/api'
        };
    </script>

    <script type="module">
        // Toast Notifications
        class ToastManager {
            constructor() {
                this.container = null;
                this.init();
            }
            init() {
                this.container = document.createElement('div');
                this.container.className = 'fixed bottom-4 right-4 z-50 space-y-2';
                document.body.appendChild(this.container);
            }
            show(message, type = 'info', duration = 5000) {
                const toast = document.createElement('div');
                const colors = {
                    success: 'bg-green-50 border-green-400 text-green-800',
                    error: 'bg-red-50 border-red-400 text-red-800',
                    warning: 'bg-yellow-50 border-yellow-400 text-yellow-800',
                    info: 'bg-blue-50 border-blue-400 text-blue-800'
                };
                const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
                toast.className = `transform transition-all duration-300 translate-x-full opacity-0 border-l-4 rounded-lg shadow-lg p-4 mb-2 ${colors[type]}`;
                toast.innerHTML = `<div class="flex items-center"><span class="text-lg font-bold mr-2">${icons[type]}</span><p class="text-sm">${message}</p><button class="ml-auto font-bold">&times;</button></div>`;
                this.container.appendChild(toast);
                setTimeout(() => toast.classList.add('translate-x-0', 'opacity-100'), 10);
                setTimeout(() => toast.remove(), duration);
                toast.querySelector('button').onclick = () => toast.remove();
                return toast;
            }
            success(m) { this.show(m, 'success'); }
            error(m) { this.show(m, 'error'); }
            warning(m) { this.show(m, 'warning'); }
            info(m) { this.show(m, 'info'); }
        }

        class LoadingManager {
            show() {
                if(this.loader) return;
                this.loader = document.createElement('div');
                this.loader.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                this.loader.innerHTML = '<div class="bg-white rounded-lg p-6"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div><p class="mt-2 text-gray-700">Loading...</p></div>';
                document.body.appendChild(this.loader);
            }
            hide() { if(this.loader) { this.loader.remove(); this.loader = null; } }
        }

        window.toast = new ToastManager();
        window.loading = new LoadingManager();

        // Simple router function
        function initRouter() {
            const path = window.location.pathname;
            const user = window.Laravel.user;
            
            console.log('Path:', path, 'User:', user);
            
            // Import and render appropriate page based on route
            if (path === '/login') {
                import('/pages/public/login.js').then(m => {
                    if (m.renderLoginPage) m.renderLoginPage();
                }).catch(err => console.error('Login page error:', err));
            } 
            else if (path === '/register') {
                import('/pages/public/register.js').then(m => {
                    if (m.renderRegisterPage) m.renderRegisterPage();
                }).catch(err => console.error('Register page error:', err));
            } 
            else if (user && user.role === 'student') {
                import('/pages/student/dashboard.js').then(m => {
                    if (m.renderStudentDashboard) m.renderStudentDashboard();
                }).catch(err => console.error('Student dashboard error:', err));
            } 
            else if (user && user.role === 'professor') {
                import('/pages/professor/dashboard.js').then(m => {
                    if (m.renderProfessorDashboard) m.renderProfessorDashboard();
                }).catch(err => console.error('Professor dashboard error:', err));
            } 
            else {
                import('/pages/public/landing.js').then(m => {
                    if (m.renderLandingPage) m.renderLandingPage();
                }).catch(err => console.error('Landing page error:', err));
            }
        }
        
        // Wait a bit then initialize router
        setTimeout(initRouter, 100);
    </script>
</body>
</html>