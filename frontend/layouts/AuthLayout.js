// Authentication Layout (Login/Register pages)
export function renderAuthLayout(content, title = 'GroupSync') {
    const layout = `
        <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
            <div class="flex min-h-screen">
                <!-- Left side - Branding -->
                <div class="hidden lg:flex lg:w-1/2 bg-blue-600 items-center justify-center">
                    <div class="text-center text-white p-12">
                        <div class="text-6xl mb-6">📊</div>
                        <h1 class="text-4xl font-bold mb-4">GroupSync</h1>
                        <p class="text-xl mb-6">AI-Powered Academic Collaboration Analytics</p>
                        <p class="text-blue-100">Monitor student contributions, detect free riders, and enhance group collaboration</p>
                    </div>
                </div>
                
                <!-- Right side - Form -->
                <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
                    <div class="w-full max-w-md">
                        ${content}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.title = title;
    return layout;
}

export default { renderAuthLayout };