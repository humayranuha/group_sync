// Spinner/Loading Component
export class Spinner {
    constructor(options = {}) {
        this.size = options.size || 'md'; // sm, md, lg
        this.color = options.color || 'blue';
        this.text = options.text || '';
        this.fullPage = options.fullPage || false;
    }

    render() {
        const sizeClass = this.getSizeClass();
        const colorClass = this.getColorClass();
        
        const spinnerHtml = `
            <div class="spinner-container ${this.fullPage ? 'fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50' : 'inline-flex items-center'}">
                <div class="text-center">
                    <div class="${sizeClass} border-4 ${colorClass} border-t-transparent rounded-full animate-spin inline-block"></div>
                    ${this.text ? `<p class="mt-2 text-sm text-gray-600">${this.text}</p>` : ''}
                </div>
            </div>
        `;
        
        return spinnerHtml;
    }

    getSizeClass() {
        const sizes = {
            sm: 'w-4 h-4',
            md: 'w-8 h-8',
            lg: 'w-12 h-12'
        };
        return sizes[this.size] || sizes.md;
    }

    getColorClass() {
        const colors = {
            blue: 'border-blue-600',
            gray: 'border-gray-600',
            green: 'border-green-600',
            red: 'border-red-600',
            yellow: 'border-yellow-600',
            white: 'border-white'
        };
        return colors[this.color] || colors.blue;
    }

    show(containerId = null) {
        const spinner = this.render();
        
        if (containerId) {
            const container = document.getElementById(containerId);
            if (container) {
                container.innerHTML = spinner;
            }
        } else if (this.fullPage) {
            const existingSpinner = document.querySelector('.spinner-container');
            if (existingSpinner) return;
            document.body.insertAdjacentHTML('beforeend', spinner);
        }
    }

    hide(containerId = null) {
        if (containerId) {
            const container = document.getElementById(containerId);
            if (container) {
                const spinner = container.querySelector('.spinner-container');
                if (spinner) spinner.remove();
            }
        } else if (this.fullPage) {
            const spinner = document.querySelector('.spinner-container');
            if (spinner) spinner.remove();
        }
    }
}

// Loading Manager for multiple loading states
export class LoadingManager {
    constructor() {
        this.activeLoaders = new Map();
        this.globalLoader = null;
    }

    showGlobal(text = 'Loading...') {
        if (this.globalLoader) return;
        
        this.globalLoader = document.createElement('div');
        this.globalLoader.className = 'fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50';
        this.globalLoader.innerHTML = `
            <div class="bg-white rounded-lg p-6 flex items-center space-x-4">
                <div class="w-8 h-8 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-gray-700">${text}</p>
            </div>
        `;
        document.body.appendChild(this.globalLoader);
    }

    hideGlobal() {
        if (this.globalLoader) {
            this.globalLoader.remove();
            this.globalLoader = null;
        }
    }

    showComponent(containerId, type = 'card') {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        const skeleton = this.createSkeleton(type);
        const loader = document.createElement('div');
        loader.className = 'component-loader absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10';
        loader.innerHTML = skeleton;
        
        container.style.position = 'relative';
        container.appendChild(loader);
        this.activeLoaders.set(containerId, loader);
    }

    hideComponent(containerId) {
        const loader = this.activeLoaders.get(containerId);
        if (loader && loader.parentNode) {
            loader.remove();
            this.activeLoaders.delete(containerId);
        }
    }

    createSkeleton(type) {
        const skeletons = {
            card: `
                <div class="animate-pulse">
                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-4"></div>
                    <div class="h-8 bg-gray-200 rounded w-1/2 mb-2"></div>
                    <div class="h-4 bg-gray-200 rounded w-full mb-2"></div>
                    <div class="h-4 bg-gray-200 rounded w-5/6"></div>
                </div>
            `,
            table: `
                <div class="animate-pulse">
                    <div class="h-10 bg-gray-200 rounded w-full mb-2"></div>
                    <div class="h-10 bg-gray-200 rounded w-full mb-2"></div>
                    <div class="h-10 bg-gray-200 rounded w-full mb-2"></div>
                    <div class="h-10 bg-gray-200 rounded w-full"></div>
                </div>
            `,
            chart: `
                <div class="animate-pulse">
                    <div class="h-64 bg-gray-200 rounded w-full"></div>
                </div>
            `,
            list: `
                <div class="animate-pulse space-y-2">
                    <div class="h-12 bg-gray-200 rounded w-full"></div>
                    <div class="h-12 bg-gray-200 rounded w-full"></div>
                    <div class="h-12 bg-gray-200 rounded w-full"></div>
                </div>
            `
        };
        
        return skeletons[type] || skeletons.card;
    }
}

export default Spinner;