import Chart from 'chart.js/auto';

function chartColors() {
    const ui = window.FrostyUI?.chart ?? {};
    return {
        palette: ui.palette ?? ['#007bff', '#198754', '#ffc107', '#6610f2', '#0dcaf0'],
        grid: ui.grid ?? 'rgba(128,128,128,0.15)',
        text: ui.text ?? '#6c757d',
    };
}

function baseOptions() {
    const { grid, text } = chartColors();
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: text, font: { family: 'Poppins' } } },
        },
        scales: {
            x: {
                ticks: { color: text, font: { family: 'Poppins', size: 11 } },
                grid: { color: grid },
            },
            y: {
                beginAtZero: true,
                ticks: { color: text, font: { family: 'Poppins', size: 11 } },
                grid: { color: grid },
            },
        },
    };
}

function barChart(id, labels, values, label, colorIndex = 0) {
    const el = document.getElementById(id);
    if (!el || !labels?.length) {
        return;
    }
    const { palette } = chartColors();
    const color = palette[colorIndex % palette.length];
    new Chart(el, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label,
                    data: values,
                    backgroundColor: color,
                    borderRadius: 6,
                },
            ],
        },
        options: {
            ...baseOptions(),
            plugins: { legend: { display: false } },
        },
    });
}

function pieChart(id, labels, values) {
    const el = document.getElementById(id);
    if (!el || !labels?.length) {
        return;
    }
    const { palette } = chartColors();
    new Chart(el, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: palette.slice(0, labels.length) }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } },
        },
    });
}

function initCharts() {
    const data = window.FrostyAnalyticsCharts;
    if (!data) {
        return;
    }
    barChart('chart-downline-orders', data.downlineOrders?.labels, data.downlineOrders?.orders, 'Orders', 0);
    barChart('chart-downline-points', data.downlineOrders?.labels, data.downlineOrders?.points, 'Points', 1);
    pieChart('chart-self-vs-downline', data.selfVsDownline?.labels, data.selfVsDownline?.orders);
    barChart('chart-level-value', data.fullNetworkOrders?.labels, data.fullNetworkOrders?.values, 'Value', 3);
}

document.addEventListener('DOMContentLoaded', initCharts);
document.addEventListener('frosty-theme-changed', () => {
    /* Charts keep colors until page refresh — acceptable for v1 */
});
