// Footer Component
export class Footer {
    constructor(options = {}) {
        this.showFooter = options.showFooter !== false;
        this.links = options.links || [
            { text: 'About', href: '/about.html' },
            { text: 'Privacy Policy', href: '/privacy.html' },
            { text: 'Terms of Service', href: '/terms.html' },
            { text: 'Contact', href: '/contact.html' }
        ];
        this.copyrightText = options.copyrightText || `© ${new Date().getFullYear()} GroupSync. All rights reserved.`;
    }

    render() {
        if (!this.showFooter) return '';
        
        return `
            <footer class="bg-white border-t border-gray-200 mt-auto">
                <div class="container mx-auto px-4 py-6">
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <div class="mb-4 md:mb-0">
                            <p class="text-sm text-gray-500">${this.copyrightText}</p>
                        </div>
                        <div class="flex space-x-6">
                            ${this.links.map(link => `
                                <a href="${link.href}" class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                                    ${link.text}
                                </a>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </footer>
        `;
    }
}

export default Footer;