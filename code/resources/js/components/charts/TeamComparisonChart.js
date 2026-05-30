// Team Comparison Chart Component
export class TeamComparisonChart {
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
        
        const chartType = this.options.chartType || 'bar'; // bar, radar
        
        let chartConfig;
        
        if (chartType === 'radar') {
            chartConfig = {
                type: 'radar',
                data: {
                    labels: this.data.metrics || ['Commits', 'PRs', 'Code Quality', 'Reviews', 'Documentation', 'Collaboration'],
                    datasets: this.data.members.map((member, index) => ({
                        label: member.name,
                        data: member.scores,
                        borderColor: this.getColor(index),
                        backgroundColor: this.getBackgroundColor(index),
                        borderWidth: 2
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                stepSize: 20
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    return `${context.dataset.label}: ${context.raw}`;
                                }
                            }
                        }
                    }
                }
            };
        } else {
            chartConfig = {
                type: 'bar',
                data: {
                    labels: this.data.members?.map(m => m.name) || [],
                    datasets: [{
                        label: 'Contribution Percentage',
                        data: this.data.members?.map(m => m.percentage) || [],
                        backgroundColor: '#3b82f6',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    return `Contribution: ${context.raw}%`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Percentage (%)'
                            }
                        }
                    }
                }
            };
        }

        this.chart = new Chart(ctx, chartConfig);
    }

    getColor(index) {
        const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
        return colors[index % colors.length];
    }

    getBackgroundColor(index) {
        const colors = ['rgba(59, 130, 246, 0.2)', 'rgba(16, 185, 129, 0.2)', 'rgba(245, 158, 11, 0.2)', 'rgba(239, 68, 68, 0.2)', 'rgba(139, 92, 246, 0.2)', 'rgba(236, 72, 153, 0.2)'];
        return colors[index % colors.length];
    }

    update(newData) {
        if (this.chart) {
            if (newData.members) {
                this.chart.data.labels = newData.members.map(m => m.name);
                this.chart.data.datasets[0].data = newData.members.map(m => m.percentage || m.scores);
            }
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

export default TeamComparisonChart;