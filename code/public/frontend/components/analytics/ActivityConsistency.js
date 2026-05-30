// Activity Consistency Component
export class ActivityConsistency {
    constructor(containerId, data, options = {}) {
        this.container = document.getElementById(containerId);
        this.data = data;
        this.options = options;
        this.chart = null;
    }

    render() {
        if (!this.container) {
            console.error(`Container element with id "${this.containerId}" not found`);
            return;
        }

        const html = `
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Activity Consistency</h3>
                
                <!-- Consistency Score -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-600">Overall Consistency Score</span>
                        <span class="text-sm font-semibold ${this.getScoreColor(this.data.overallScore)}">${this.data.overallScore || 0}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="h-3 rounded-full transition-all duration-500 ${this.getScoreBarColor(this.data.overallScore)}" 
                             style="width: ${this.data.overallScore || 0}%"></div>
                    </div>
                </div>
                
                <!-- Weekly Consistency -->
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Weekly Consistency</h4>
                    <div class="space-y-3">
                        ${this.data.weeklyData?.map(week => `
                            <div>
                                <div class="flex justify-between text-xs text-gray-600 mb-1">
                                    <span>Week ${week.week}</span>
                                    <span>${week.score}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full ${this.getScoreBarColor(week.score)}" 
                                         style="width: ${week.score}%"></div>
                                </div>
                            </div>
                        `).join('') || '<p class="text-gray-500 text-sm">No weekly data available</p>'}
                    </div>
                </div>
                
                <!-- Consistency Trend -->
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Consistency Trend</h4>
                    <canvas id="${this.container.id}-trend-chart" height="200"></canvas>
                </div>
                
                <!-- AI Insight -->
                ${this.data.insight ? `
                    <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                        <p class="text-sm text-blue-800">💡 ${this.data.insight}</p>
                    </div>
                ` : ''}
            </div>
        `;

        this.container.innerHTML = html;
        
        // Render trend chart
        if (this.data.trendData) {
            this.renderTrendChart();
        }
    }

    getScoreColor(score) {
        if (score >= 80) return 'text-green-600';
        if (score >= 60) return 'text-yellow-600';
        return 'text-red-600';
    }

    getScoreBarColor(score) {
        if (score >= 80) return 'bg-green-500';
        if (score >= 60) return 'bg-yellow-500';
        return 'bg-red-500';
    }

    renderTrendChart() {
        const canvasId = `${this.container.id}-trend-chart`;
        const canvas = document.getElementById(canvasId);
        
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.data.trendData.map(d => `Week ${d.week}`),
                datasets: [{
                    label: 'Consistency Score',
                    data: this.data.trendData.map(d => d.score),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Consistency Score (%)'
                        }
                    }
                }
            }
        });
    }

    update(newData) {
        this.data = newData;
        this.render();
    }
}

export default ActivityConsistency;