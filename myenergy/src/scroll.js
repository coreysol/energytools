import scrollama from 'scrollama';
import { highlightTimeWindow } from './annotations.js';

export function initScroll(chartState, events) {
  const stepsContainer = document.getElementById('scroll-steps');
  stepsContainer.innerHTML = '';

  events.forEach((ev, i) => {
    const step = document.createElement('div');
    step.className = 'step';
    step.dataset.index = i;
    step.dataset.anchor = ev.anchor;
    if (ev.end) step.dataset.end = ev.end;

    const dateLabel = ev.sensitivity === 'soft'
      ? formatSoftDate(ev.anchor)
      : formatDate(ev.anchor);

    step.innerHTML = `
      <div class="step-content">
        <h3>${ev.title}</h3>
        <p>${ev.body}</p>
        <div class="step-date">${dateLabel}</div>
      </div>
    `;
    stepsContainer.appendChild(step);
  });

  const scroller = scrollama();

  scroller
    .setup({
      step: '#scroll-steps .step',
      offset: 0.5,
      debug: false,
    })
    .onStepEnter(({ element }) => {
      document.querySelectorAll('.step').forEach(s => s.classList.remove('is-active'));
      element.classList.add('is-active');
      const anchor = element.dataset.anchor;
      const end = element.dataset.end || null;
      highlightTimeWindow(chartState, anchor, end);
    });

  window.addEventListener('resize', scroller.resize);

  return scroller;
}

function formatDate(ym) {
  const [year, month] = ym.split('-');
  const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  return `${months[parseInt(month, 10) - 1]} ${year}`;
}

function formatSoftDate(ym) {
  const [year, month] = ym.split('-');
  const m = parseInt(month, 10);
  if (m <= 3) return `Early ${year}`;
  if (m <= 6) return `Spring ${year}`;
  if (m <= 9) return `Summer ${year}`;
  return `Late ${year}`;
}
