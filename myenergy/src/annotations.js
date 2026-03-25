import * as d3 from 'd3';

const parseYM = d3.timeParse('%Y-%m');

export function drawAnnotations(chartState, events) {
  const { g, x, y, innerH } = chartState;

  g.selectAll('.event-marker').remove();
  g.selectAll('.highlight-band').remove();

  events.forEach(ev => {
    const startDate = parseYM(ev.anchor);
    if (!startDate) return;

    const endDate = ev.end ? parseYM(ev.end) : null;

    if (endDate) {
      g.append('rect')
        .attr('class', 'highlight-band')
        .attr('x', x(startDate))
        .attr('y', 0)
        .attr('width', Math.max(x(endDate) - x(startDate), 4))
        .attr('height', innerH)
        .attr('fill', 'var(--color-band)');
    }

    const markerG = g.append('g').attr('class', 'event-marker');

    markerG.append('line')
      .attr('x1', x(startDate))
      .attr('x2', x(startDate))
      .attr('y1', 0)
      .attr('y2', innerH);
  });
}

export function highlightTimeWindow(chartState, anchor, end) {
  const { g, x, innerH } = chartState;

  g.selectAll('.active-highlight').remove();

  const startDate = parseYM(anchor);
  if (!startDate) return;

  const endDate = end ? parseYM(end) : d3.timeMonth.offset(startDate, 1);

  g.insert('rect', ':first-child')
    .attr('class', 'active-highlight')
    .attr('x', x(startDate))
    .attr('y', 0)
    .attr('width', Math.max(x(endDate) - x(startDate), 12))
    .attr('height', innerH)
    .attr('fill', 'var(--color-highlight)')
    .attr('rx', 3);
}
