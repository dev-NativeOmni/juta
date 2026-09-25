import Chart from 'chart.js/auto';
import ChartDataLabels from 'chartjs-plugin-datalabels';
import * as htmlToImage from 'html-to-image';

window.Chart = Chart;
window.ChartDataLabels = ChartDataLabels;
window.htmlToImage = htmlToImage;

import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

const components = import.meta.glob('./components/**/*.js', { eager: true });
Object.entries(components).forEach(([path, definition]) => {
    const name = path.split('/').pop().replace('.js', '');
    Alpine.data(name, definition.default);
});

Alpine.start();
