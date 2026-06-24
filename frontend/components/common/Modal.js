// Modal Component
export class Modal {
    constructor(options = {}) {
        this.id = options.id || 'modal-' + Date.now();
        this.title = options.title || 'Modal Title';
        this.content = options.content || '';
        this.size = options.size || 'md'; // sm, md, lg, xl
        this.closeOnOverlayClick = options.closeOnOverlayClick !== false;
        this.showCloseButton = options.showCloseButton !== false;
        this.onConfirm = options.onConfirm || null;
        this.onCancel = options.onCancel || null;
        this.confirmText = options.confirmText || 'Confirm';
        this.cancelText = options.cancelText || 'Cancel';
        this.showFooter = options.showFooter !== false;
    }

    render() {
        const sizeClasses = this.getSizeClasses();
        
        const modalHtml = `
            <div id="${this.id}" class="modal-overlay fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden" style="z-index: 1000;">
                <div class="relative top-20 mx-auto p-4 w-full ${sizeClasses}">
                    <div class="relative bg-white rounded-lg shadow-xl">
                        <!-- Modal header -->
                        <div class="flex items-center justify-between p-4 border-b rounded-t">
                            <h3 class="text-xl font-semibold text-gray-900">
                                ${this.title}
                            </h3>
                            ${this.showCloseButton ? `
                                <button type="button" class="modal-close text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            ` : ''}
                        </div>
                        
                        <!-- Modal body -->
                        <div class="p-6">
                            ${this.content}
                        </div>
                        
                        <!-- Modal footer -->
                        ${this.showFooter ? `
                            <div class="flex items-center justify-end space-x-3 p-4 border-t rounded-b">
                                ${this.onCancel ? `<button type="button" class="modal-cancel px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">${this.cancelText}</button>` : ''}
                                ${this.onConfirm ? `<button type="button" class="modal-confirm px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">${this.confirmText}</button>` : ''}
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
        
        return modalHtml;
    }

    getSizeClasses() {
        const sizes = {
            sm: 'max-w-md',
            md: 'max-w-lg',
            lg: 'max-w-2xl',
            xl: 'max-w-4xl'
        };
        return sizes[this.size] || sizes.md;
    }

    show() {
        const modal = document.getElementById(this.id);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            this.attachEvents();
        }
    }

    hide() {
        const modal = document.getElementById(this.id);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    attachEvents() {
        const modal = document.getElementById(this.id);
        if (!modal) return;
        
        const closeBtn = modal.querySelector('.modal-close');
        const cancelBtn = modal.querySelector('.modal-cancel');
        const confirmBtn = modal.querySelector('.modal-confirm');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.hide();
                if (this.onCancel) this.onCancel();
            });
        }
        
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                this.hide();
                if (this.onCancel) this.onCancel();
            });
        }
        
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                if (this.onConfirm) this.onConfirm();
                this.hide();
            });
        }
        
        if (this.closeOnOverlayClick) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.hide();
                    if (this.onCancel) this.onCancel();
                }
            });
        }
    }

    setContent(content) {
        const modal = document.getElementById(this.id);
        if (modal) {
            const body = modal.querySelector('.p-6');
            if (body) {
                body.innerHTML = content;
            }
        }
    }
}

export default Modal;