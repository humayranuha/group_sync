// Contribution Chart Component
export class ContributionChart {
    constructor(canvasId, data, options = {}) {
        this.canvas = document.getElementById(canvasId);
        this.data = data;
        this.options = options;
        this.chart = null;
        this.type = options.type || 'bar'; // bar, line, pie
    }

    render() {
        if (!this.canvas) {
            console.error(`Canvas element with id "${this.canvasId}" not found`);
            return;
        }

        const ctx = this.canvas.getContext('2d');
        
        const chartConfig = {
            type: this.type,
            data: {
                labels: this.data.labels || [],
                datasets: [{
                    label: this.data.datasetLabel || 'Contributions',
                    data: this.data.values || [],
                    backgroundColor: this.getBackgroundColor(),
                    borderColor: '#3b82f6',
                    borderWidth: 1,
                    tension: 0.4,
                    fill: this.type === 'line'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                return `${context.dataset.label}: ${context.parsed.y}`;
                            }
                        }
                    }
                },
                scales: this.type !== 'pie' ? {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: this.options.yAxisLabel || 'Number of Contributions'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: this.options.xAxisLabel || 'Students'
                        }
                    }
                } : {}
            }
        };

        this.chart = new Chart(ctx, chartConfig);
    }

    getBackgroundColor() {
        if (this.type === 'line') {
            return 'rgba(59, 130, 246, 0.1)';
        }
        if (this.type === 'pie') {
            return [
                '#3b82f6', '#60a5fa', '#93c5fd', '#bfdbfe', 
                '#1e3a8a', '#2563eb', '#1d4ed8', '#1e40af'
            ];
        }
        return 'rgba(59, 130, 246, 0.7)';
    }

    update(newData) {
        if (this.chart) {
            this.chart.data.labels = newData.labels || this.chart.data.labels;
            this.chart.data.datasets[0].data = newData.values || this.chart.data.datasets[0].data;
            this.chart.update();
        }
    }

    destroy() {
        if (this.chart) {
            this.chart.destroy();
            this.chart = null;
        }
    }
}

export default ContributionChart;