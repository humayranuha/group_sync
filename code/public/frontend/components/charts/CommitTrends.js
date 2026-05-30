// Commit Trends Chart Component
export class CommitTrends {
    constructor(canvasId, data, options = {}) {
        this.canvas = document.getElementById(canvasId);
        this.data = data;
        this.options = options;
        this.chart = null;
    }

    render() {
        if (!this.canvas) {
            console.error(`Canvas element with id "${this.canvasId}" not found`);
            return;
        }

        const ctx = this.canvas.getContext('2d');
        
        const datasets = [];
        
        // Main commits dataset
        if (this.data.commits) {
            datasets.push({
                label: 'Commits',
                data: this.data.commits,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true
            });
        }
        
        // Pull requests dataset
        if (this.data.pullRequests) {
            datasets.push({
                label: 'Pull Requests',
                data: this.data.pullRequests,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true
            });
        }
        
        // Lines added dataset
        if (this.data.linesAdded) {
            datasets.push({
                label: 'Lines Added',
                data: this.data.linesAdded,
                borderColor: '#f59e0b',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                tension: 0.4,
                fill: true,
                yAxisID: 'y1'
            });
        }

        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.data.labels || [],
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 6
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                let label = context.dataset.label || '';
                                let value = context.parsed.y;
                                if (context.dataset.label === 'Lines Added') {
                                    value = value.toLocaleString();
                                }
                                return `${label}: ${value}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Commits / PRs',
                            color: '#3b82f6'
                        },
                        grid: {
                            color: '#e5e7eb'
                        }
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Lines of Code',
                            color: '#f59e0b'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: this.options.xAxisLabel || 'Time'
                        }
                    }
                }
            }
        });
    }

    update(newData) {
        if (this.chart) {
            if (newData.labels) {
                this.chart.data.labels = newData.labels;
            }
            if (newData.commits) {
                this.chart.data.datasets[0].data = newData.commits;
            }
            if (newData.pullRequests && this.chart.data.datasets[1]) {
                this.chart.data.datasets[1].data = newData.pullRequests;
            }
            this.chart.update();
        }
    }

    addDataPoint(label, commits, pullRequests = null, linesAdded = null) {
        this.chart.data.labels.push(label);
        this.chart.data.datasets[0].data.push(commits);
        if (pullRequests !== null && this.chart.data.datasets[1]) {
            this.chart.data.datasets[1].data.push(pullRequests);
        }
        if (linesAdded !== null && this.chart.data.datasets[2]) {
            this.chart.data.datasets[2].data.push(linesAdded);
        }
        this.chart.update();
    }

    destroy() {
        if (this.chart) {
            this.chart.destroy();
            this.chart = null;
        }
    }
}

export default CommitTrends;