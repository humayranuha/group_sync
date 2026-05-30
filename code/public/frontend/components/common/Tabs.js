// Tabs Component
export class Tabs {
    constructor(options = {}) {
        this.id = options.id || 'tabs-' + Date.now();
        this.tabs = options.tabs || [];
        this.activeTab = options.activeTab || 0;
        this.onTabChange = options.onTabChange || (() => {});
    }

    render() {
        return `
            <div id="${this.id}" class="tabs-container">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        ${this.tabs.map((tab, index) => `
                            <button class="tab-btn ${index === this.activeTab ? 'active' : ''} 
                                ${index === this.activeTab 
                                    ? 'border-blue-500 text-blue-600' 
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'} 
                                whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                                data-tab-index="${index}">
                                ${tab.icon ? `<span class="mr-2">${tab.icon}</span>` : ''}
                                ${tab.label}
                                ${tab.badge ? `<span class="ml-2 px-2 py-0.5 text-xs rounded-full ${tab.badgeClass || 'bg-gray-100 text-gray-600'}">${tab.badge}</span>` : ''}
                            </button>
                        `).join('')}
                    </nav>
                </div>
                <div class="tab-contents mt-4">
                    ${this.tabs.map((tab, index) => `
                        <div class="tab-content ${index === this.activeTab ? '' : 'hidden'}" data-content-index="${index}">
                            ${tab.content}
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    attachEvents() {
        const container = document.getElementById(this.id);
        if (!container) return;
        
        const buttons = container.querySelectorAll('.tab-btn');
        const contents = container.querySelectorAll('.tab-content');
        
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                const index = parseInt(button.dataset.tabIndex);
                this.switchTab(index);
            });
        });
    }

    switchTab(index) {
        const container = document.getElementById(this.id);
        if (!container) return;
        
        const buttons = container.querySelectorAll('.tab-btn');
        const contents = container.querySelectorAll('.tab-content');
        
        buttons.forEach((btn, i) => {
            if (i === index) {
                btn.classList.add('active', 'border-blue-500', 'text-blue-600');
                btn.classList.remove('border-transparent', 'text-gray-500');
            } else {
                btn.classList.remove('active', 'border-blue-500', 'text-blue-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            }
        });
        
        contents.forEach((content, i) => {
            if (i === index) {
                content.classList.remove('hidden');
            } else {
                content.classList.add('hidden');
            }
        });
        
        this.activeTab = index;
        this.onTabChange(index, this.tabs[index]);
    }

    setActiveTab(index) {
        if (index >= 0 && index < this.tabs.length) {
            this.switchTab(index);
        }
    }

    updateTabContent(index, newContent) {
        if (index >= 0 && index < this.tabs.length) {
            this.tabs[index].content = newContent;
            
            const container = document.getElementById(this.id);
            if (container) {
                const contentDiv = container.querySelector(`.tab-content[data-content-index="${index}"]`);
                if (contentDiv) {
                    contentDiv.innerHTML = newContent;
                }
            }
        }
    }

    updateTabBadge(index, badge, badgeClass = 'bg-gray-100 text-gray-600') {
        if (index >= 0 && index < this.tabs.length) {
            this.tabs[index].badge = badge;
            this.tabs[index].badgeClass = badgeClass;
            
            const container = document.getElementById(this.id);
            if (container) {
                const button = container.querySelector(`.tab-btn[data-tab-index="${index}"]`);
                if (button) {
                    const existingBadge = button.querySelector('.ml-2');
                    if (badge) {
                        if (existingBadge) {
                            existingBadge.textContent = badge;
                            existingBadge.className = `ml-2 px-2 py-0.5 text-xs rounded-full ${badgeClass}`;
                        } else {
                            const badgeSpan = document.createElement('span');
                            badgeSpan.className = `ml-2 px-2 py-0.5 text-xs rounded-full ${badgeClass}`;
                            badgeSpan.textContent = badge;
                            button.appendChild(badgeSpan);
                        }
                    } else if (existingBadge) {
                        existingBadge.remove();
                    }
                }
            }
        }
    }
}

export default Tabs;