// Sidebar Component
export class Sidebar {
    constructor(options = {}) {
        this.isOpen = options.isOpen || false;
        this.onToggle = options.onToggle || (() => {});
        this.menuItems = options.menuItems || [];
        this.user = options.user || null;
    }

    render() {
        return `
            <aside id="sidebar" class="fixed inset-y-0 left-0 bg-white shadow-lg transition-transform duration-300 z-30 ${this.isOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'}" style="width: 280px;">
                <div class="flex flex-col h-full">
                    <!-- Sidebar Header -->
                    <div class="flex items-center justify-between h-16 px-4 bg-gradient-to-r from-blue-600 to-blue-700">
                        <div class="flex items-center">
                            <h1 class="text-xl font-bold text-white">GroupSync</h1>
                            ${this.user?.role === 'professor' ? '<span class="ml-2 text-xs text-blue-200">Professor</span>' : ''}
                        </div>
                        <button id="close-sidebar" class="md:hidden text-white hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Navigation -->
                    <nav class="flex-1 px-3 py-6 space-y-1 overflow-y-auto">
                        ${this.menuItems.map(item => `
                            <a href="${item.path}" 
                               class="nav-item flex items-center px-3 py-2.5 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors group ${item.active ? 'active bg-blue-50 text-blue-700' : ''}"
                               data-page="${item.id}">
                                <span class="text-xl mr-3">${item.icon}</span>
                                <span class="text-sm font-medium">${item.name}</span>
                                ${item.badge ? `<span class="ml-auto px-2 py-0.5 text-xs rounded-full ${item.badgeClass}">${item.badge}</span>` : ''}
                            </a>
                        `).join('')}
                    </nav>
                    
                    <!-- User Section -->
                    <div class="p-4 border-t border-gray-200">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-400 to-blue-600 flex items-center justify-center text-white font-semibold">
                                ${this.getUserInitials()}
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-gray-800">${this.getUserName()}</p>
                                <p class="text-xs text-gray-500 capitalize">${this.user?.role || 'User'}</p>
                            </div>
                            <button id="logout-btn" class="p-2 text-gray-400 hover:text-red-600 transition-colors" title="Logout">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </aside>
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
        const closeBtn = document.getElementById('close-sidebar');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close());
        }
        
        const logoutBtn = document.getElementById('logout-btn');
        if (logoutBtn && window.authService) {
            logoutBtn.addEventListener('click', () => window.authService.logout());
        }
    }

    open() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('translate-x-0');
            this.isOpen = true;
            this.onToggle(true);
        }
    }

    close() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar && window.innerWidth < 768) {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('translate-x-0');
            this.isOpen = false;
            this.onToggle(false);
        }
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
}

export default Sidebar;