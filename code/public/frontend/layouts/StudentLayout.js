// Student Dashboard Layout
export function renderStudentLayout(content, currentPage = 'dashboard') {
    const user = JSON.parse(localStorage.getItem('groupsync_user_data') || '{}');
    
    const layout = `
        <div class="min-h-screen bg-gray-50">
            ${renderSidebar(user, currentPage)}
            ${renderMobileMenuButton()}
            <main class="md:ml-64">
                ${renderHeader(user)}
                <div class="px-4 py-6 md:px-8">
                    ${content}
                </div>
            </main>
        </div>
    `;
    
    document.title = `Student Dashboard - GroupSync`;
    return layout;
}

function renderSidebar(user, currentPage) {
    const navItems = [
        { id: 'dashboard', name: 'Dashboard', icon: '📊', path: '/student/dashboard.html' },
        { id: 'group', name: 'My Group', icon: '👥', path: '/student/group-details.html' },
        { id: 'repository', name: 'GitHub Connection', icon: '🔗', path: '/student/repository-connection.html' },
        { id: 'analytics', name: 'Analytics', icon: '📈', path: '/student/contribution-analytics.html' },
        { id: 'ai-feedback', name: 'AI Feedback', icon: '🤖', path: '/student/ai-feedback.html' }
    ];
    
    return `
        <aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg transform -translate-x-full md:translate-x-0 transition-transform duration-300 z-30">
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-center h-16 bg-blue-600">
                    <h1 class="text-xl font-bold text-white">GroupSync</h1>
                </div>
                
                <nav class="flex-1 px-2 py-4 space-y-1">
                    ${navItems.map(item => `
                        <a href="${item.path}" 
                           class="nav-item ${currentPage === item.id ? 'active' : ''} flex items-center px-4 py-2 text-gray-700 rounded-lg hover:bg-gray-100">
                            <span class="mr-3">${item.icon}</span>
                            <span>${item.name}</span>
                        </a>
                    `).join('')}
                </nav>
                
                <div class="p-4 border-t">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white">
                            ${user.first_name ? user.first_name[0] : 'S'}
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-700">${user.first_name || 'Student'} ${user.last_name || ''}</p>
                            <p class="text-xs text-gray-500">Student</p>
                        </div>
                        <button id="logout-btn" class="text-red-600 hover:text-red-800">🚪</button>
                    </div>
                </div>
            </div>
        </aside>
    `;
}

function renderMobileMenuButton() {
    return `
        <button id="mobile-menu-btn" class="fixed top-4 left-4 z-40 md:hidden bg-blue-600 text-white p-2 rounded-lg shadow-lg">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
    `;
}

function renderHeader(user) {
    return `
        <nav class="bg-white shadow-sm">
            <div class="px-4 py-3">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-semibold text-gray-800">Student Dashboard</h2>
                    <div class="flex items-center space-x-4">
                        <button id="notifications-btn" class="relative text-gray-600 hover:text-gray-800">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                            <span id="notification-badge" class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full hidden"></span>
                        </button>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-600">${user.first_name || 'Student'}</span>
                            <img src="${user.avatar || 'https://via.placeholder.com/32'}" class="w-8 h-8 rounded-full">
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    `;
}

export function initStudentLayout() {
    // Mobile menu toggle
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    
    if (mobileBtn && sidebar) {
        mobileBtn.addEventListener('click', () => {
            sidebar.classList.toggle('translate-x-0');
        });
    }
    
    // Logout handler
    const logoutBtn = document.getElementById('logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', () => {
            if (window.authService) {
                window.authService.logout();
            }
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth < 768 && sidebar && !sidebar.contains(e.target) && !mobileBtn?.contains(e.target)) {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
        }
    });
}

export default { renderStudentLayout, initStudentLayout };