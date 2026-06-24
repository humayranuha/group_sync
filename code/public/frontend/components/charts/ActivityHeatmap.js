// Activity Heatmap Component
export class ActivityHeatmap {
    constructor(containerId, data, options = {}) {
        this.container = document.getElementById(containerId);
        this.data = data;
        this.options = options;
        this.days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    }

    render() {
        if (!this.container) {
            console.error(`Container element with id "${this.containerId}" not found`);
            return;
        }

        const hours = this.options.hours || Array.from({ length: 12 }, (_, i) => `${i + 8}:00`);
        
        let html = `
            <div class="overflow-x-auto">
                <div class="inline-block min-w-full">
                    <div class="grid" style="grid-template-columns: 60px repeat(${hours.length}, 1fr);">
                        <!-- Empty corner -->
                        <div class="p-2"></div>
                        <!-- Hour headers -->
                        ${hours.map(hour => `
                            <div class="text-center text-xs text-gray-500 font-medium p-1">${hour}</div>
                        `).join('')}
                        
                        ${this.days.map((day, dayIndex) => `
                            ${this.renderRow(day, dayIndex, hours)}
                        `).join('')}
                    </div>
                </div>
            </div>
        `;

        this.container.innerHTML = html;
    }

    renderRow(day, dayIndex, hours) {
        return `
            <div class="contents">
                <div class="p-2 text-sm font-medium text-gray-700 flex items-center">${day}</div>
                ${hours.map((_, hourIndex) => {
                    const value = this.data[dayIndex]?.[hourIndex] || 0;
                    const colorClass = this.getColorClass(value);
                    return `
                        <div class="p-1">
                            <div class="${colorClass} rounded h-8 flex items-center justify-center text-xs text-white font-medium transition-transform hover:scale-110 cursor-pointer"
                                 data-day="${day}" data-hour="${hourIndex + 8}" data-value="${value}"
                                 title="${day} ${hourIndex + 8}:00 - ${value} contributions">
                                ${value > 0 ? value : ''}
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    }

    getColorClass(value) {
        if (value === 0) return 'bg-gray-100';
        if (value < 5) return 'bg-green-200 text-green-800';
        if (value < 10) return 'bg-green-400';
        if (value < 20) return 'bg-green-600';
        return 'bg-green-800';
    }

    update(newData) {
        this.data = newData;
        this.render();
    }

    attachEvents(onCellClick) {
        this.container.querySelectorAll('[data-day]').forEach(cell => {
            cell.addEventListener('click', () => {
                const day = cell.dataset.day;
                const hour = cell.dataset.hour;
                const value = cell.dataset.value;
                if (onCellClick) {
                    onCellClick({ day, hour, value });
                }
            });
        });
    }
}

export default ActivityHeatmap;