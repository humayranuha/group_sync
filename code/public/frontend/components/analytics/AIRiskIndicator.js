// AI Risk Indicator Component
export class AIRiskIndicator {
    constructor(containerId, data, options = {}) {
        this.container = document.getElementById(containerId);
        this.data = data;
        this.options = options;
    }

    render() {
        if (!this.container) {
            console.error(`Container element with id "${this.containerId}" not found`);
            return;
        }

        const riskLevel = this.data.riskLevel || 'Low';
        const riskColor = this.getRiskColor(riskLevel);
        const riskBgColor = this.getRiskBgColor(riskLevel);
        
        const html = `
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">AI Risk Assessment</h3>
                    <span class="px-3 py-1 rounded-full text-sm font-medium ${riskBgColor} ${riskColor}">
                        ${riskLevel} Risk
                    </span>
                </div>
                
                <!-- Risk Score -->
                <div class="mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-600">Risk Score</span>
                        <span class="text-sm font-semibold ${riskColor}">${this.data.riskScore || 0}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="h-3 rounded-full transition-all duration-500 ${this.getRiskBarColor(riskLevel)}" 
                             style="width: ${this.data.riskScore || 0}%"></div>
                    </div>
                </div>
                
                <!-- Risk Factors -->
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Risk Factors</h4>
                    <div class="space-y-2">
                        ${this.data.factors?.map(factor => `
                            <div class="flex items-center justify-between p-2 ${factor.severity === 'high' ? 'bg-red-50' : factor.severity === 'medium' ? 'bg-yellow-50' : 'bg-green-50'} rounded-lg">
                                <div class="flex items-center">
                                    <span class="text-lg mr-2">${this.getFactorIcon(factor.severity)}</span>
                                    <span class="text-sm text-gray-700">${factor.description}</span>
                                </div>
                                <span class="text-xs font-medium ${factor.severity === 'high' ? 'text-red-600' : factor.severity === 'medium' ? 'text-yellow-600' : 'text-green-600'}">
                                    ${factor.severity.toUpperCase()}
                                </span>
                            </div>
                        `).join('') || '<p class="text-gray-500 text-sm">No risk factors detected</p>'}
                    </div>
                </div>
                
                <!-- Recommendations -->
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Recommendations</h4>
                    <ul class="space-y-2">
                        ${this.data.recommendations?.map(rec => `
                            <li class="flex items-start">
                                <span class="text-blue-500 mr-2">•</span>
                                <span class="text-sm text-gray-600">${rec}</span>
                            </li>
                        `).join('') || '<li class="text-gray-500 text-sm">No recommendations available</li>'}
                    </ul>
                </div>
                
                <!-- Warning Message for High Risk -->
                ${riskLevel === 'High' ? `
                    <div class="mt-4 p-3 bg-red-100 border-l-4 border-red-500 rounded">
                        <p class="text-sm text-red-800">
                            ⚠️ ${this.data.warningMessage || 'Immediate attention required. Student showing signs of free riding behavior.'}
                        </p>
                    </div>
                ` : ''}
            </div>
        `;

        this.container.innerHTML = html;
    }

    getRiskColor(riskLevel) {
        switch(riskLevel) {
            case 'High': return 'text-red-700';
            case 'Medium': return 'text-yellow-700';
            default: return 'text-green-700';
        }
    }

    getRiskBgColor(riskLevel) {
        switch(riskLevel) {
            case 'High': return 'bg-red-100';
            case 'Medium': return 'bg-yellow-100';
            default: return 'bg-green-100';
        }
    }

    getRiskBarColor(riskLevel) {
        switch(riskLevel) {
            case 'High': return 'bg-red-500';
            case 'Medium': return 'bg-yellow-500';
            default: return 'bg-green-500';
        }
    }

    getFactorIcon(severity) {
        switch(severity) {
            case 'high': return '🔴';
            case 'medium': return '🟡';
            default: return '🟢';
        }
    }

    update(newData) {
        this.data = newData;
        this.render();
    }
}

export default AIRiskIndicator;