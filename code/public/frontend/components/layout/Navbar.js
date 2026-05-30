// Navbar Component
export class Navbar {
    constructor(options = {}) {
        this.title = options.title || 'Dashboard';
        this.user = options.user || null;
        this.onMenuClick = options.onMenuClick || (() => {});
        this.onNotificationsClick = options.onNotificationsClick || (() => {});
        this.notificationCount = options.notificationCount || 0;
    }

    render() {
        return `
            <nav class="bg-white shadow-sm sticky top-0 z-20">
                <div class="px-4 py-3">
                    <div class="flex items-center justify-between">
                        <!-- Left section -->
                        <div class="flex items-center space-x-4">
                            <button id="menu-toggle" class="p-2 rounded-lg text-gray-600 hover:bg-gray-100 md:hidden transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                </svg>
                            </button>
                            <h2 class="text-xl font-semibold text-gray-800">${this.title}</h2>
                        </div>
                        
                        <!-- Right section -->
                        <div class="flex items-center space-x-3">
                            <!-- Search -->
                            <div class="hidden md:block relative">
                                <input type="text" id="global-search" placeholder="Search..." 
                                       class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            
                            <!-- Notifications -->
                            <div class="relative">
                                <button id="notifications-btn" class="p-2 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors relative">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                    </svg>
                                    ${this.notificationCount > 0 ? `<span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>` : ''}
                                </button>
                            </div>
                            
                            <!-- User Menu -->
                            <div class="relative">
                                <button id="user-menu-btn" class="flex items-center space-x-2 p-1 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-400 to-blue-600 flex items-center justify-center text-white text-sm font-semibold">
                                        ${this.getUserInitials()}
                                    </div>
                                    <span class="hidden md:inline text-sm text-gray-700">${this.getUserName()}</span>
                                    <svg class="hidden md:block w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <!-- Dropdown Menu -->
                                <div id="user-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-50">
                                    <div class="py-1">
                                        <a href="/profile.html" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <span class="mr-2">👤</span> Profile
                                        </a>
                                        <a href="/settings.html" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <span class="mr-2">⚙️</span> Settings
                                        </a>
                                        <hr class="my-1">
                                        <button id="dropdown-logout" class="w-full text-left block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                            <span class="mr-2">🚪</span> Logout
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        `;
    }

    getUserInitials() {
        if (!this.user) return 'U';
        const first = this.user.first_name?.[0] || '';
        const last = this.user.last_name?.[0] || '';
        return (first + last).toUpperCase() || 'U';
    }

    getUserName() {
        if (!this.user) return 'User';
        return `${this.user.first_name || ''} ${this.user.last_name || ''}`.trim() || 'User';
    }

    attachEvents() {
        const menuToggle = document.getElementById('menu-toggle');
        if (menuToggle) {
            menuToggle.addEventListener('click', () => this.onMenuClick());
        }
        
        const notificationsBtn = document.getElementById('notifications-btn');
        if (notificationsBtn) {
            notificationsBtn.addEventListener('click', () => this.onNotificationsClick());
        }
        
        const userMenuBtn = document.getElementById('user-menu-btn');
        const userDropdown = document.getElementById('user-dropdown');
        
        if (userMenuBtn && userDropdown) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('hidden');
            });
            
            document.addEventListener('click', (e) => {
                if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.add('hidden');
                }
            });
        }
        
        const dropdownLogout = document.getElementById('dropdown-logout');
        if (dropdownLogout && window.authService) {
            dropdownLogout.addEventListener('click', () => window.authService.logout());
        }
        
        // Global search
        const searchInput = document.getElementById('global-search');
        if (searchInput) {
            const debouncedSearch = this.debounce((value) => {
                console.log('Searching for:', value);
                // Implement search functionality
            }, 300);
            
            searchInput.addEventListener('input', (e) => debouncedSearch(e.target.value));
        }
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    updateNotificationCount(count) {
        this.notificationCount = count;
        const btn = document.getElementById('notifications-btn');
        if (btn) {
            const existingBadge = btn.querySelector('.absolute');
            if (count > 0) {
                if (!existingBadge) {
                    const badge = document.createElement('span');
                    badge.className = 'absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full';
                    btn.appendChild(badge);
                }
            } else if (existingBadge) {
                existingBadge.remove();
            }
        }
    }
}

export default Navbar;