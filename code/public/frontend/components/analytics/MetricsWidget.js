// Metrics Widget Component
export class MetricsWidget {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        this.title = options.title || 'Metrics';
        this.metrics = options.metrics || [];
        this.columns = options.columns || 4;
        this.onMetricClick = options.onMetricClick || (() => {});
    }

    render() {
        if (!this.container) {
            console.error(`Container element with id "${this.containerId}" not found`);
            return;
        }

        const gridClass = this.getGridClass();
        
        const html = `
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">${this.title}</h3>
                <div class="${gridClass} gap-4">
                    ${this.metrics.map(metric => `
                        <div class="metric-card bg-gray-50 rounded-lg p-4 cursor-pointer hover:shadow-md transition-all duration-200"
                             data-metric-id="${metric.id}">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">${metric.label}</p>
                                    <p class="text-2xl font-bold text-gray-900 mt-1">${this.formatValue(metric.value, metric.format)}</p>
                                    ${metric.trend ? this.renderTrend(metric.trend) : ''}
                                </div>
                                <div class="p-3 rounded-full ${metric.iconBg || 'bg-blue-100'}">
                                    <span class="text-xl">${metric.icon || '📊'}</span>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;

        this.container.innerHTML = html;
        this.attachEvents();
    }

    getGridClass() {
        const columns = {
            1: 'grid-cols-1',
            2: 'grid-cols-1 md:grid-cols-2',
            3: 'grid-cols-1 md:grid-cols-3',
            4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4'
        };
        return `grid ${columns[this.columns] || columns[4]}`;
    }

    formatValue(value, format) {
        if (value === undefined || value === null) return '0';
        
        switch(format) {
            case 'percentage':
                return `${value}%`;
            case 'currency':
                return `$${value.toLocaleString()}`;
            case 'number':
                return value.toLocaleString();
            case 'decimal':
                return value.toFixed(2);
            default:
                return value;
        }
    }

    renderTrend(trend) {
        const isPositive = trend.value >= 0;
        const icon = isPositive ? '↑' : '↓';
        const colorClass = isPositive ? 'text-green-600' : 'text-red-600';
        
        return `
            <p class="text-xs ${colorClass} mt-1">
                ${icon} ${Math.abs(trend.value)}% ${trend.label || 'from last period'}
            </p>
        `;
    }

    attachEvents() {
        const cards = this.container.querySelectorAll('.metric-card');
        cards.forEach(card => {
            card.addEventListener('click', () => {
                const metricId = card.dataset.metricId;
                const metric = this.metrics.find(m => m.id === metricId);
                if (metric) {
                    this.onMetricClick(metric);
                }
            });
        });
    }

    updateMetric(metricId, newValue) {
        const metric = this.metrics.find(m => m.id === metricId);
        if (metric) {
            metric.value = newValue;
            const valueElement = this.container.querySelector(`.metric-card[data-metric-id="${metricId}"] .text-2xl`);
            if (valueElement) {
                valueElement.textContent = this.formatValue(newValue, metric.format);
            }
        }
    }

    updateMetrics(newMetrics) {
        this.metrics = newMetrics;
        this.render();
    }
}

export default MetricsWidget;