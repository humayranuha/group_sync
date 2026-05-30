// Card Component
export class Card {
    constructor(options = {}) {
        this.title = options.title || null;
        this.subtitle = options.subtitle || null;
        this.content = options.content || '';
        this.footer = options.footer || null;
        this.hoverable = options.hoverable || false;
        this.padding = options.padding !== false;
        this.className = options.className || '';
    }

    render() {
        const hoverClass = this.hoverable ? 'card-hover hover:shadow-lg hover:transform hover:-translate-y-1 transition-all duration-300' : '';
        
        return `
            <div class="bg-white rounded-lg shadow-md overflow-hidden ${hoverClass} ${this.className}">
                ${this.renderHeader()}
                <div class="${this.padding ? 'p-6' : ''}">
                    ${this.content}
                </div>
                ${this.renderFooter()}
            </div>
        `;
    }

    renderHeader() {
        if (!this.title && !this.subtitle) return '';
        
        return `
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                ${this.title ? `<h3 class="text-lg font-semibold text-gray-800">${this.title}</h3>` : ''}
                ${this.subtitle ? `<p class="text-sm text-gray-500 mt-1">${this.subtitle}</p>` : ''}
            </div>
        `;
    }

    renderFooter() {
        if (!this.footer) return '';
        
        return `
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                ${this.footer}
            </div>
        `;
    }
}

export default Card;