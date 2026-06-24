// Select Component
export class Select {
    constructor(options = {}) {
        this.id = options.id || 'select-' + Date.now();
        this.name = options.name || '';
        this.label = options.label || '';
        this.options = options.options || [];
        this.value = options.value || '';
        this.placeholder = options.placeholder || 'Select an option';
        this.required = options.required || false;
        this.disabled = options.disabled || false;
        this.error = options.error || '';
        this.hint = options.hint || '';
        this.onChange = options.onChange || (() => {});
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
                
                <select id="${this.id}" 
                        name="${this.name}"
                        class="w-full px-3 py-2 border rounded-md shadow-sm ${errorClass} focus:outline-none focus:ring-2 transition-colors duration-200 bg-white"
                        ${this.required ? 'required' : ''}
                        ${this.disabled ? 'disabled' : ''}>
                    ${this.placeholder ? `<option value="">${this.placeholder}</option>` : ''}
                    ${this.options.map(opt => `
                        <option value="${opt.value}" ${this.value === opt.value ? 'selected' : ''}>
                            ${opt.label}
                        </option>
                    `).join('')}
                </select>
                
                ${this.hint && !hasError ? `
                    <p class="mt-1 text-xs text-gray-500">${this.hint}</p>
                ` : ''}
                
                ${hasError ? `
                    <p class="mt-1 text-xs text-red-600">${this.error}</p>
                ` : ''}
            </div>
        `;
    }

    attachEvents() {
        const select = document.getElementById(this.id);
        if (select) {
            select.addEventListener('change', (e) => {
                this.value = e.target.value;
                this.onChange(e.target.value, e);
            });
        }
    }

    getValue() {
        const select = document.getElementById(this.id);
        return select ? select.value : this.value;
    }

    setValue(value) {
        const select = document.getElementById(this.id);
        if (select) {
            select.value = value;
            this.value = value;
        }
    }

    setOptions(options) {
        this.options = options;
        const select = document.getElementById(this.id);
        if (select) {
            const currentValue = select.value;
            select.innerHTML = '';
            
            if (this.placeholder) {
                select.innerHTML += `<option value="">${this.placeholder}</option>`;
            }
            
            options.forEach(opt => {
                select.innerHTML += `<option value="${opt.value}" ${currentValue === opt.value ? 'selected' : ''}>${opt.label}</option>`;
            });
        }
    }

    setError(error) {
        this.error = error;
        const select = document.getElementById(this.id);
        
        if (select) {
            if (error) {
                select.classList.add('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
                select.classList.remove('border-gray-300', 'focus:ring-blue-500', 'focus:border-blue-500');
            } else {
                select.classList.remove('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
                select.classList.add('border-gray-300', 'focus:ring-blue-500', 'focus:border-blue-500');
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

export default Select;