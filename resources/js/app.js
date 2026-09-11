import Alpine from 'alpinejs';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

// Match the Tailwind sans stack and the muted slate palette used across the UI.
Chart.defaults.font.family =
    "ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = '#64748b'; // slate-500 — ticks, legend, tooltips
Chart.defaults.borderColor = '#e2e8f0'; // slate-200 — grid lines
Chart.defaults.maintainAspectRatio = false;

/**
 * Renders a Chart.js chart from a payload supplied by <x-ui.chart>, so no chart
 * configuration is ever inlined in a Blade template.
 */
Alpine.data('chart', (config) => ({
    chart: null,

    init() {
        this.chart = new Chart(this.$refs.canvas, {
            type: config.type,
            data: config.data,
            options: config.options ?? {},
        });
    },

    destroy() {
        this.chart?.destroy();
        this.chart = null;
    },
}));

window.Alpine = Alpine;

Alpine.start();
