// Main Layout Component
export function renderMainLayout(content, options = {}) {
    const { title = 'GroupSync', showNavbar = true, showFooter = true } = options;
    
    const layout = `
        <div class="min-h-screen bg-gray-50">
            ${showNavbar ? renderNavbar() : ''}
            <main class="container mx-auto px-4 py-8">
                ${content}
            </main>
            ${showFooter ? renderFooter() : ''}
        </div>
    `;
    
    document.title = title;
    return layout;
}

function renderNavbar() {
    return `
        <nav class="bg-white shadow-sm">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <h1 class="text-2xl font-bold text-blue-600">GroupSync</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="/login.html" class="text-gray-700 hover:text-gray-900">Login</a>
                        <a href="/register.html" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Register</a>
                    </div>
                </div>
            </div>
        </nav>
    `;
}

function renderFooter() {
    return `
        <footer class="bg-white border-t mt-12">
            <div class="container mx-auto px-4 py-6">
                <p class="text-center text-gray-600 text-sm">&copy; 2024 GroupSync. All rights reserved.</p>
            </div>
        </footer>
    `;
}

export default { renderMainLayout };