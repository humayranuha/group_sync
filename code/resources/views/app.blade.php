<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GroupSync - Academic Collaboration Analytics Platform</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <style>
        .animate-spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
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
        // Simple router - no complex imports
        (function() {
            // Get current path
            const path = window.location.pathname;
            console.log('Current path:', path);
            
            // Function to load HTML file
            async function loadPage(url) {
                try {
                    const response = await fetch(url);
                    const html = await response.text();
                    document.getElementById('app').innerHTML = html;
                } catch (error) {
                    console.error('Error loading page:', error);
                    document.getElementById('app').innerHTML = '<div class="text-center text-red-600 p-8">Error loading page. Please try again.</div>';
                }
            }
            
            // Route to appropriate page
            if (path === '/' || path === '/index.html') {
                loadPage('/frontend/pages/public/landing.html');
            } 
            else if (path === '/login.html') {
                loadPage('/frontend/pages/public/login.html');
            }
            else if (path === '/register.html') {
                loadPage('/frontend/pages/public/register.html');
            }
            else if (path.includes('/student/')) {
                loadPage('/frontend/pages/student/dashboard.html');
            }
            else if (path.includes('/professor/')) {
                loadPage('/frontend/pages/professor/dashboard.html');
            }
            else {
                // Try to load the requested file
                loadPage('/frontend' + path);
            }
        })();
    </script>
</body>
</html>