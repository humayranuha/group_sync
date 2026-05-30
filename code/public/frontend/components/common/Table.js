// Table Component
export class Table {
    constructor(options = {}) {
        this.columns = options.columns || [];
        this.data = options.data || [];
        this.responsive = options.responsive !== false;
        this.striped = options.striped || false;
        this.hoverable = options.hoverable !== false;
        this.bordered = options.bordered || false;
        this.onRowClick = options.onRowClick || null;
        this.emptyMessage = options.emptyMessage || 'No data available';
    }

    render() {
        const responsiveClass = this.responsive ? 'overflow-x-auto' : '';
        const borderedClass = this.bordered ? 'border border-gray-200' : '';
        
        return `
            <div class="${responsiveClass}">
                <table class="min-w-full divide-y divide-gray-200 ${borderedClass}">
                    ${this.renderHeader()}
                    ${this.renderBody()}
                </table>
            </div>
        `;
    }

    renderHeader() {
        return `
            <thead class="bg-gray-50">
                <tr>
                    ${this.columns.map(col => `
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ${col.label}
                        </th>
                    `).join('')}
                </tr>
            </thead>
        `;
    }

    renderBody() {
        if (!this.data || this.data.length === 0) {
            return `
                <tbody>
                    <tr>
                        <td colspan="${this.columns.length}" class="px-6 py-12 text-center text-gray-500">
                            ${this.emptyMessage}
                        </td>
                    </tr>
                </tbody>
            `;
        }
        
        const stripedClass = this.striped ? 'even:bg-gray-50' : '';
        const hoverClass = this.hoverable ? 'hover:bg-gray-50' : '';
        
        return `
            <tbody class="bg-white divide-y divide-gray-200">
                ${this.data.map((row, index) => `
                    <tr class="${stripedClass} ${hoverClass} ${this.onRowClick ? 'cursor-pointer' : ''}" data-row-index="${index}">
                        ${this.columns.map(col => `
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ${this.formatCellValue(row[col.key], col)}
                            </td>
                        `).join('')}
                    </tr>
                `).join('')}
            </tbody>
        `;
    }

    formatCellValue(value, column) {
        if (column.format && typeof column.format === 'function') {
            return column.format(value);
        }
        
        if (column.type === 'badge') {
            return `<span class="px-2 py-1 text-xs rounded-full ${this.getBadgeClass(value)}">${value}</span>`;
        }
        
        if (column.type === 'progress') {
            return `
                <div class="flex items-center">
                    <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: ${value}%"></div>
                    </div>
                    <span class="text-sm text-gray-600">${value}%</span>
                </div>
            `;
        }
        
        if (column.type === 'avatar') {
            const initials = this.getInitials(value);
            return `
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold text-sm">
                        ${initials}
                    </div>
                    <span class="ml-3">${value}</span>
                </div>
            `;
        }
        
        return value || '-';
    }

    getBadgeClass(value) {
        const badgeClasses = {
            'Active': 'bg-green-100 text-green-800',
            'Moderate': 'bg-yellow-100 text-yellow-800',
            'Passive': 'bg-orange-100 text-orange-800',
            'Free Rider': 'bg-red-100 text-red-800',
            'High': 'bg-red-100 text-red-800',
            'Medium': 'bg-yellow-100 text-yellow-800',
            'Low': 'bg-green-100 text-green-800'
        };
        return badgeClasses[value] || 'bg-gray-100 text-gray-800';
    }

    getInitials(name) {
        if (!name) return '?';
        return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
    }

    attachEvents() {
        if (!this.onRowClick) return;
        
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('click', (e) => {
                const index = parseInt(row.dataset.rowIndex);
                if (!isNaN(index) && this.data[index]) {
                    this.onRowClick(this.data[index], index);
                }
            });
        });
    }

    updateData(newData) {
        this.data = newData;
        const container = document.querySelector('.table-container');
        if (container) {
            container.innerHTML = this.render();
            this.attachEvents();
        }
    }
}

export default Table;