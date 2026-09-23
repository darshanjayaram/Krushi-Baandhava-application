import './bootstrap';
import Chart from 'chart.js/auto';

window.Chart = Chart;

// Global chart registry to avoid canvas re-use errors on dynamic updates
window.KrushiActiveCharts = window.KrushiActiveCharts || {};

/**
 * Initialize or update the Price & Arrival Trend Chart.
 * Dual-axis: Left = Modal Price (₹/Quintal), Right = Arrivals (Quintals).
 */
window.initPriceTrendChart = function (canvasId, options = {}) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    if (window.KrushiActiveCharts[canvasId]) {
        window.KrushiActiveCharts[canvasId].destroy();
    }

    const ctx = canvas.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.35)'); // emerald-500
    gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

    const labels = options.labels || [];
    const modalPrices = options.modalPrices || [];
    const minPrices = options.minPrices || [];
    const maxPrices = options.maxPrices || [];
    const arrivals = options.arrivals || [];

    const datasets = [
        {
            type: 'line',
            label: 'ಮಾದರಿ ಬೆಲೆ (Modal Rate ₹)',
            data: modalPrices,
            borderColor: '#059669', // emerald-600
            backgroundColor: gradient,
            borderWidth: 2.5,
            fill: true,
            tension: 0.3,
            pointRadius: labels.length > 30 ? 0 : 3.5,
            pointHoverRadius: 6,
            pointBackgroundColor: '#047857',
            yAxisID: 'y',
        }
    ];

    // Optional Arrivals dataset
    if (arrivals.length > 0 && arrivals.some(val => val > 0)) {
        datasets.push({
            type: 'bar',
            label: 'ಆವಕ (Arrivals Qtl)',
            data: arrivals,
            backgroundColor: 'rgba(209, 213, 219, 0.55)', // slate-300
            hoverBackgroundColor: 'rgba(156, 163, 175, 0.8)',
            borderRadius: 4,
            yAxisID: 'y1',
            order: 2,
        });
    }

    const chart = new Chart(ctx, {
        data: {
            labels: labels,
            datasets: datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        font: { size: 12, weight: '600' },
                        color: '#374151',
                    },
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.92)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (context) {
                            if (context.dataset.yAxisID === 'y') {
                                return ` ಬೆಲೆ: ₹${Number(context.raw).toLocaleString('en-IN')}/ಕ್ವಿಂಟಾಲ್`;
                            }
                            return ` ಆವಕ: ${Number(context.raw).toLocaleString('en-IN')} ಕ್ವಿಂಟಾಲ್`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#6b7280',
                        font: { size: 11 },
                        maxRotation: 45,
                    },
                },
                y: {
                    type: 'linear',
                    position: 'left',
                    grid: { color: 'rgba(243, 244, 246, 0.9)' },
                    ticks: {
                        color: '#059669',
                        font: { size: 11, weight: '600' },
                        callback: function (val) {
                            return '₹' + Number(val).toLocaleString('en-IN');
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    grid: { display: false },
                    ticks: {
                        color: '#6b7280',
                        font: { size: 11 },
                    }
                }
            }
        }
    });

    window.KrushiActiveCharts[canvasId] = chart;
    return chart;
};

/**
 * Initialize 12-Month Seasonal Index Bar Chart.
 */
window.initSeasonalityChart = function (canvasId, monthlyProfile = []) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return null;

    if (window.KrushiActiveCharts[canvasId]) {
        window.KrushiActiveCharts[canvasId].destroy();
    }

    const ctx = canvas.getContext('2d');
    const labels = monthlyProfile.map(m => m.name_kn.split(' ')[0]);
    const seasonalIndices = monthlyProfile.map(m => m.seasonal_index);
    const avgPrices = monthlyProfile.map(m => m.avg_price);

    const backgroundColors = seasonalIndices.map(val => {
        if (val >= 1.06) return '#10b981'; // Peak - emerald-500
        if (val >= 1.01) return '#3b82f6'; // Above avg - blue-500
        if (val >= 0.95) return '#f59e0b'; // Normal - amber-500
        return '#f43f5e'; // Lean - rose-500
    });

    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'ಋತುಮಾನ ಸೂಚ್ಯಂಕ (Seasonal Index)',
                data: seasonalIndices,
                backgroundColor: backgroundColors,
                borderRadius: 6,
                maxBarThickness: 36,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.92)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (context) {
                            const index = context.raw;
                            const idx = context.dataIndex;
                            const price = avgPrices[idx];
                            const diff = Math.round((index - 1) * 100);
                            const sign = diff >= 0 ? '+' : '';
                            return [
                                ` ಸೂಚ್ಯಂಕ: ${index} (${sign}${diff}%)`,
                                ` ಸರಾಸರಿ ಬೆಲೆ: ₹${Number(price).toLocaleString('en-IN')}/ಕ್ವಿಂಟಾಲ್`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: '#374151', font: { size: 11, weight: '500' } }
                },
                y: {
                    min: 0.7,
                    suggestedMax: 1.3,
                    grid: { color: 'rgba(243, 244, 246, 0.9)' },
                    ticks: {
                        color: '#6b7280',
                        font: { size: 11 },
                        callback: function (val) {
                            return val.toFixed(2);
                        }
                    }
                }
            }
        }
    });

    window.KrushiActiveCharts[canvasId] = chart;
    return chart;
};
