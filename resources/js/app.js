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
    const chartHeight = canvas.clientHeight || 320;
    const gradient = ctx.createLinearGradient(0, 0, 0, chartHeight);
    gradient.addColorStop(0, 'rgba(16, 185, 129, 0.28)'); // emerald-500
    gradient.addColorStop(0.5, 'rgba(16, 185, 129, 0.08)');
    gradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

    const labels = options.labels || [];
    const modalPrices = options.modalPrices || [];
    const minPrices = options.minPrices || [];
    const maxPrices = options.maxPrices || [];
    const arrivals = options.arrivals || [];
    const isEn = options.locale === 'en';

    const datasets = [
        {
            type: 'line',
            label: isEn ? 'Modal Rate (₹)' : 'ಮಾದರಿ ದರ (₹)',
            data: modalPrices,
            borderColor: '#059669', // emerald-600
            backgroundColor: gradient,
            borderWidth: 2.8,
            fill: true,
            tension: 0.35,
            pointRadius: labels.length > 35 ? 0 : 3.5,
            pointHoverRadius: 6,
            pointBackgroundColor: '#047857',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointHoverBackgroundColor: '#059669',
            pointHoverBorderColor: '#ffffff',
            pointHoverBorderWidth: 3,
            yAxisID: 'y',
            order: 1,
        }
    ];

    // Optional Arrivals dataset
    const validArrivals = arrivals.map(Number).filter(v => !isNaN(v) && v > 0);
    const maxArrival = validArrivals.length > 0 ? Math.max(...validArrivals) : 0;
    if (validArrivals.length > 0) {
        datasets.push({
            type: 'bar',
            label: isEn ? 'Daily Arrivals (Qtl)' : 'ದೈನಂದಿನ ಆವಕ (ಕ್ವಿಂಟಾಲ್)',
            data: arrivals,
            backgroundColor: 'rgba(203, 213, 225, 0.55)', // slate-300
            hoverBackgroundColor: 'rgba(148, 163, 184, 0.85)',
            borderRadius: { topLeft: 4, topRight: 4, bottomLeft: 0, bottomRight: 0 },
            yAxisID: 'y1',
            order: 2,
            barPercentage: 0.55,
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
                    display: false, // Handled cleanly by custom HTML legend toolbar
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.94)',
                    titleColor: '#F8FAFC',
                    titleFont: { size: 12, weight: '700' },
                    bodyColor: '#E2E8F0',
                    bodyFont: { size: 12 },
                    borderColor: 'rgba(255, 255, 255, 0.12)',
                    borderWidth: 1,
                    padding: { top: 10, bottom: 10, left: 14, right: 14 },
                    cornerRadius: 10,
                    boxPadding: 6,
                    usePointStyle: true,
                    callbacks: {
                        title: function (items) {
                            return `📅 ${items[0].label}`;
                        },
                        label: function (context) {
                            if (context.dataset.yAxisID === 'y') {
                                return isEn 
                                    ? ` Modal Rate: ₹${Number(context.raw).toLocaleString('en-IN')}/Quintal`
                                    : ` ಮಾದರಿ ಬೆಲೆ: ₹${Number(context.raw).toLocaleString('en-IN')}/ಕ್ವಿಂಟಾಲ್`;
                            }
                            return isEn
                                ? ` Arrivals: ${Number(context.raw).toLocaleString('en-IN')} Quintals`
                                : ` ಆವಕ ಪ್ರಮಾಣ: ${Number(context.raw).toLocaleString('en-IN')} ಕ್ವಿಂಟಾಲ್`;
                        },
                        afterBody: function (items) {
                            const idx = items[0].dataIndex;
                            const min = minPrices[idx];
                            const max = maxPrices[idx];
                            if (min && max && Number(min) !== Number(max)) {
                                return isEn
                                    ? `\nRange: ₹${Number(min).toLocaleString('en-IN')} – ₹${Number(max).toLocaleString('en-IN')}`
                                    : `\nದರ ಶ್ರೇಣಿ: ₹${Number(min).toLocaleString('en-IN')} – ₹${Number(max).toLocaleString('en-IN')}`;
                            }
                            return '';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#64748b',
                        font: { size: 11, weight: '500' },
                        maxRotation: 45,
                    },
                },
                y: {
                    type: 'linear',
                    position: 'left',
                    grace: '8%',
                    grid: {
                        color: 'rgba(241, 245, 249, 0.9)',
                        borderDash: [4, 4],
                    },
                    ticks: {
                        color: '#059669',
                        font: { size: 11, weight: '700' },
                        callback: function (val) {
                            return '₹' + Number(val).toLocaleString('en-IN');
                        }
                    }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    grid: { display: false },
                    suggestedMax: maxArrival > 0 ? maxArrival * 3.2 : undefined,
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 10, weight: '600' },
                        callback: function (val) {
                            return Number(val).toLocaleString('en-IN') + ' Q';
                        }
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
