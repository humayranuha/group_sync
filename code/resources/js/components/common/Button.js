// Button Component
export class Button {
    constructor(options = {}) {
        this.text = options.text || 'Button';
        this.type = options.type || 'primary'; // primary, secondary, danger, success, warning, outline
        this.size = options.size || 'md'; // sm, md, lg
        this.disabled = options.disabled || false;
        this.loading = options.loading || false;
        this.fullWidth = options.fullWidth || false;
        this.icon = options.icon || null;
        this.onClick = options.onClick || (() => {});
    }

    render() {
        const baseClasses = this.getBaseClasses();
        const sizeClasses = this.getSizeClasses();
        const typeClasses = this.getTypeClasses();
        const widthClass = this.fullWidth ? 'w-full' : '';
        const disabledClass = this.disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer';
        
        return `
            <button class="${baseClasses} ${sizeClasses} ${typeClasses} ${widthClass} ${disabledClass} inline-flex items-center justify-center transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2" 
                    ${this.disabled ? 'disabled' : ''}
                    id="${this.id || 'btn-' + Date.now()}">
                ${this.loading ? this.getLoadingSpinner() : ''}
                ${this.icon && !this.loading ? `<span class="mr-2">${this.icon}</span>` : ''}
                ${!this.loading ? this.text : ''}
            </button>
        `;
    }

    getBaseClasses() {
        return 'rounded-lg font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';
    }

    getSizeClasses() {
        const sizes = {
            sm: 'px-3 py-1.5 text-sm',
            md: 'px-4 py-2 text-base',
            lg: 'px-6 py-3 text-lg'
        };
        return sizes[this.size] || sizes.md;
    }

    getTypeClasses() {
        const types = {
            primary: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
            secondary: 'bg-gray-200 text-gray-800 hover:bg-gray-300 focus:ring-gray-500',
            danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
            success: 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-500',
            warning: 'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-500',
            outline: 'bg-transparent border border-gray-300 text-gray-700 hover:bg-gray-50 focus:ring-gray-500'
        };
        return types[this.type] || types.primary;
    }

    getLoadingSpinner() {
        return `
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        `;
    }

    attachEvents() {
        const btn = document.getElementById(this.id);
        if (btn && !this.disabled) {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (!this.loading) {
                    this.onClick(e);
                }
            });
        }
    }

    setLoading(loading) {
        this.loading = loading;
        const btn = document.getElementById(this.id);
        if (btn) {
            if (loading) {
                btn.innerHTML = this.getLoadingSpinner() + ' Loading...';
                btn.disabled = true;
            } else {
                btn.innerHTML = this.icon ? `<span class="mr-2">${this.icon}</span>${this.text}` : this.text;
                btn.disabled = false;
            }
        }
    }
}

export default Button;