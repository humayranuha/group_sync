// Input Component
export class Input {
    constructor(options = {}) {
        this.id = options.id || 'input-' + Date.now();
        this.name = options.name || '';
        this.type = options.type || 'text';
        this.label = options.label || '';
        this.placeholder = options.placeholder || '';
        this.value = options.value || '';
        this.required = options.required || false;
        this.disabled = options.disabled || false;
        this.error = options.error || '';
        this.hint = options.hint || '';
        this.onChange = options.onChange || (() => {});
        this.onInput = options.onInput || (() => {});
    }

    render() {
        const hasError = this.error && this.error.length > 0;
        const errorClass = hasError ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500';
        
        return `
            <div class="mb-4">
                ${this.label ? `
                    <label for="${this.id}" class="block text-sm font-medium text-gray-700 mb-1">
                        ${this.label}
                        ${this.required ? '<span class="text-red-500 ml-1">*</span>' : ''}
                    </label>
                ` : ''}
                
                <input type="${this.type}" 
                       id="${this.id}" 
                       name="${this.name}" 
                       value="${this.escapeHtml(this.value)}"
                       placeholder="${this.placeholder}"
                       class="w-full px-3 py-2 border rounded-md shadow-sm ${errorClass} focus:outline-none focus:ring-2 transition-colors duration-200"
                       ${this.required ? 'required' : ''}
                       ${this.disabled ? 'disabled' : ''}>
                
                ${this.hint && !hasError ? `
                    <p class="mt-1 text-xs text-gray-500">${this.hint}</p>
                ` : ''}
                
                ${hasError ? `
                    <p class="mt-1 text-xs text-red-600">${this.error}</p>
                ` : ''}
            </div>
        `;
    }

    escapeHtml(str) {
        if (!str) return '';
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }

    attachEvents() {
        const input = document.getElementById(this.id);
        if (input) {
            input.addEventListener('change', (e) => {
                this.value = e.target.value;
                this.onChange(e.target.value, e);
            });
            
            input.addEventListener('input', (e) => {
                this.value = e.target.value;
                this.onInput(e.target.value, e);
            });
        }
    }

    getValue() {
        const input = document.getElementById(this.id);
        return input ? input.value : this.value;
    }

    setValue(value) {
        const input = document.getElementById(this.id);
        if (input) {
            input.value = value;
            this.value = value;
        }
    }

    setError(error) {
        this.error = error;
        const input = document.getElementById(this.id);
        
        if (input) {
            if (error) {
                input.classList.add('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
                input.classList.remove('border-gray-300', 'focus:ring-blue-500', 'focus:border-blue-500');
            } else {
                input.classList.remove('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
                input.classList.add('border-gray-300', 'focus:ring-blue-500', 'focus:border-blue-500');
            }
        }
    }

    clearError() {
        this.setError('');
    }

    reset() {
        this.setValue('');
        this.clearError();
    }
}

export default Input;