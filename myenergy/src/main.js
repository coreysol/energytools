import './style.css';
import { createChart, buildLegend } from './chart.js';
import { drawAnnotations } from './annotations.js';
import { initScroll } from './scroll.js';

async function loadJSON(path) {
  const resp = await fetch(path);
  if (!resp.ok) throw new Error(`Failed to load ${path}: ${resp.status}`);
  return resp.json();
}

function renderStats(data) {
  const container = document.getElementById('stats');
  const total = d => d.grid_kwh + (d.solar_kwh ?? 0);
  const preSolar = data.filter(d => d.year_month < '2012-06');
  const postSolar = data.filter(d => d.year_month >= '2012-06');

  const avgPre = preSolar.reduce((s, d) => s + total(d), 0) / preSolar.length;
  const avgPost = postSolar.reduce((s, d) => s + total(d), 0) / postSolar.length;
  const netExportMonths = data.filter(d => d.grid_kwh < 0).length;

  const totalSolar = data
    .filter(d => d.solar_kwh != null)
    .reduce((s, d) => s + d.solar_kwh, 0);

  const stats = [
    { value: Math.round(avgPre).toLocaleString(), label: 'Avg monthly kWh before solar' },
    { value: Math.round(avgPost).toLocaleString(), label: 'Avg monthly kWh after solar' },
    { value: netExportMonths, label: 'Net-export months' },
    { value: Math.round(totalSolar).toLocaleString(), label: 'Total solar kWh produced' },
  ];

  container.innerHTML = stats.map(s => `
    <div class="stat-card">
      <div class="stat-value">${s.value}</div>
      <div class="stat-label">${s.label}</div>
    </div>
  `).join('');
}

async function main() {
  const [data, events] = await Promise.all([
    loadJSON('./energy_story.json'),
    loadJSON('./events.json'),
  ]);

  const hasSolar = data.some(d => d.solar_kwh != null);
  const chartState = createChart('#chart', data);
  buildLegend('#chart-legend', hasSolar);
  drawAnnotations(chartState, events);
  initScroll(chartState, events);
  renderStats(data);

  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      const newState = createChart('#chart', data);
      buildLegend('#chart-legend', hasSolar);
      drawAnnotations(newState, events);
    }, 200);
  });
}

main().catch(err => console.error('Failed to initialize:', err));
