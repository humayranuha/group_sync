// File Upload Component
export class FileUpload {
    constructor(options = {}) {
        this.id = options.id || 'file-upload-' + Date.now();
        this.name = options.name || '';
        this.label = options.label || '';
        this.accept = options.accept || '.pdf,.jpg,.jpeg,.png';
        this.multiple = options.multiple || false;
        this.required = options.required || false;
        this.disabled = options.disabled || false;
        this.error = options.error || '';
        this.hint = options.hint || '';
        this.onChange = options.onChange || (() => {});
        this.files = [];
    }

    render() {
        const hasError = this.error && this.error.length > 0;
        
        return `
            <div class="mb-4">
                ${this.label ? `
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        ${this.label}
                        ${this.required ? '<span class="text-red-500 ml-1">*</span>' : ''}
                    </label>
                ` : ''}
                
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 transition-colors duration-200">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="${this.id}" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                                <span>Upload a file</span>
                                <input id="${this.id}" name="${this.name}" type="file" class="sr-only" 
                                       ${this.accept ? `accept="${this.accept}"` : ''}
                                       ${this.multiple ? 'multiple' : ''}
                                       ${this.disabled ? 'disabled' : ''}>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">
                            ${this.accept.replace(/,/g, ', ')} up to 5MB
                        </p>
                        ${this.hint ? `<p class="text-xs text-gray-400 mt-1">${this.hint}</p>` : ''}
                    </div>
                </div>
                
                <div id="${this.id}-preview" class="mt-3 space-y-2"></div>
                
                ${hasError ? `
                    <p class="mt-1 text-xs text-red-600">${this.error}</p>
                ` : ''}
            </div>
        `;
    }

    attachEvents() {
        const input = document.getElementById(this.id);
        const dropZone = input?.closest('.border-2');
        
        if (input) {
            input.addEventListener('change', (e) => this.handleFiles(e.target.files));
            
            if (dropZone) {
                dropZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropZone.classList.add('border-blue-500', 'bg-blue-50');
                });
                
                dropZone.addEventListener('dragleave', (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('border-blue-500', 'bg-blue-50');
                });
                
                dropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('border-blue-500', 'bg-blue-50');
                    this.handleFiles(e.dataTransfer.files);
                });
            }
        }
    }

    handleFiles(fileList) {
        const files = Array.from(fileList);
        const validFiles = [];
        
        for (const file of files) {
            if (file.size > 5 * 1024 * 1024) {
                this.setError(`${file.name} exceeds 5MB limit`);
                continue;
            }
            validFiles.push(file);
        }
        
        if (this.multiple) {
            this.files = [...this.files, ...validFiles];
        } else {
            this.files = validFiles.slice(0, 1);
        }
        
        this.updatePreview();
        this.onChange(this.files);
    }

    updatePreview() {
        const previewContainer = document.getElementById(`${this.id}-preview`);
        if (!previewContainer) return;
        
        if (this.files.length === 0) {
            previewContainer.innerHTML = '';
            return;
        }
        
        previewContainer.innerHTML = this.files.map((file, index) => `
            <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <span class="text-lg mr-2">${this.getFileIcon(file.type)}</span>
                    <div>
                        <p class="text-sm font-medium text-gray-700">${file.name}</p>
                        <p class="text-xs text-gray-500">${this.formatFileSize(file.size)}</p>
                    </div>
                </div>
                <button type="button" class="remove-file text-red-500 hover:text-red-700" data-index="${index}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `).join('');
        
        previewContainer.querySelectorAll('.remove-file').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const index = parseInt(btn.dataset.index);
                this.files.splice(index, 1);
                this.updatePreview();
                this.onChange(this.files);
            });
        });
    }

    getFileIcon(fileType) {
        if (fileType.startsWith('image/')) return '🖼️';
        if (fileType === 'application/pdf') return '📄';
        return '📎';
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    getFiles() {
        return this.files;
    }

    clearFiles() {
        this.files = [];
        this.updatePreview();
        const input = document.getElementById(this.id);
        if (input) input.value = '';
    }

    setError(error) {
        this.error = error;
    }

    clearError() {
        this.error = '';
    }

    reset() {
        this.clearFiles();
        this.clearError();
    }
}

export default FileUpload;