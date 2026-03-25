import * as d3 from 'd3';

const MARGIN = { top: 20, right: 20, bottom: 48, left: 55 };

function aggregateToYears(data) {
  const groups = new Map();

  for (const d of data) {
    const year = d.year_month.slice(0, 4);
    if (!groups.has(year)) {
      groups.set(year, { year: +year, grid_vals: [], solar_vals: [], total_vals: [] });
    }
    const g = groups.get(year);
    g.grid_vals.push(d.grid_kwh);
    g.total_vals.push(d.grid_kwh + (d.solar_kwh ?? 0));
    if (d.solar_kwh != null) g.solar_vals.push(d.solar_kwh);
  }

  const result = [];
  for (const [, g] of groups) {
    if (g.grid_vals.length < 6) continue;
    const sum = arr => arr.reduce((s, v) => s + v, 0);
    result.push({
      year_month: `${g.year}-07`,
      grid_kwh: Math.round(sum(g.grid_vals)),
      solar_kwh: g.solar_vals.length > 0 ? Math.round(sum(g.solar_vals)) : null,
      total_kwh: Math.round(sum(g.total_vals)),
    });
  }

  result.sort((a, b) => a.year_month.localeCompare(b.year_month));
  return result;
}

export function createChart(container, data) {
  const wrapper = d3.select(container);
  wrapper.selectAll('*').remove();

  const rect = wrapper.node().getBoundingClientRect();
  const width = rect.width;
  const height = rect.height;
  const innerW = width - MARGIN.left - MARGIN.right;
  const innerH = height - MARGIN.top - MARGIN.bottom;

  const svg = wrapper.append('svg')
    .attr('viewBox', `0 0 ${width} ${height}`)
    .attr('preserveAspectRatio', 'xMidYMid meet');

  const g = svg.append('g')
    .attr('transform', `translate(${MARGIN.left},${MARGIN.top})`);

  const parseYM = d3.timeParse('%Y-%m');
  const yearly = aggregateToYears(data);
  const items = yearly.map(d => ({
    ...d,
    date: parseYM(d.year_month),
  }));

  const x = d3.scaleTime()
    .domain(d3.extent(items, d => d.date))
    .range([0, innerW]);

  const yMax = d3.max(items, d => Math.max(d.total_kwh, d.solar_kwh ?? 0));
  const y = d3.scaleLinear()
    .domain([0, yMax * 1.08])
    .nice()
    .range([innerH, 0]);

  // Horizontal grid lines
  g.append('g')
    .attr('class', 'grid-line')
    .call(d3.axisLeft(y)
      .ticks(6)
      .tickSize(-innerW)
      .tickFormat('')
    )
    .call(sel => sel.select('.domain').remove());

  // Total consumption area
  const totalArea = d3.area()
    .x(d => x(d.date))
    .y0(y(0))
    .y1(d => y(d.total_kwh))
    .curve(d3.curveMonotoneX);

  g.append('path')
    .datum(items)
    .attr('class', 'area-total')
    .attr('d', totalArea)
    .attr('fill', 'var(--color-total-area)')
    .attr('stroke', 'none');

  // Total consumption line
  const totalLine = d3.line()
    .x(d => x(d.date))
    .y(d => y(d.total_kwh))
    .curve(d3.curveMonotoneX);

  g.append('path')
    .datum(items)
    .attr('class', 'line-total')
    .attr('d', totalLine)
    .attr('fill', 'none')
    .attr('stroke', 'var(--color-total)')
    .attr('stroke-width', 2);

  // Grid consumption line
  const gridLine = d3.line()
    .x(d => x(d.date))
    .y(d => y(Math.max(d.grid_kwh, 0)))
    .curve(d3.curveMonotoneX);

  g.append('path')
    .datum(items)
    .attr('class', 'line-grid')
    .attr('d', gridLine)
    .attr('fill', 'none')
    .attr('stroke', 'var(--color-grid)')
    .attr('stroke-width', 1.5)
    .attr('stroke-dasharray', '6 3');

  // Solar production
  const solarItems = items.filter(d => d.solar_kwh != null);

  if (solarItems.length > 0) {
    const solarArea = d3.area()
      .x(d => x(d.date))
      .y0(y(0))
      .y1(d => y(d.solar_kwh))
      .curve(d3.curveMonotoneX);

    g.append('path')
      .datum(solarItems)
      .attr('class', 'area-solar')
      .attr('d', solarArea)
      .attr('fill', 'var(--color-solar-area)')
      .attr('stroke', 'none');

    const solarLine = d3.line()
      .x(d => x(d.date))
      .y(d => y(d.solar_kwh))
      .curve(d3.curveMonotoneX);

    g.append('path')
      .datum(solarItems)
      .attr('class', 'line-solar')
      .attr('d', solarLine)
      .attr('fill', 'none')
      .attr('stroke', 'var(--color-solar)')
      .attr('stroke-width', 2);
  }

  // Axes
  g.append('g')
    .attr('class', 'axis x-axis')
    .attr('transform', `translate(0,${innerH})`)
    .call(d3.axisBottom(x)
      .ticks(d3.timeYear.every(1))
      .tickFormat(d3.timeFormat("'%y"))
      .tickSizeOuter(0)
    )
    .selectAll('text')
      .attr('transform', 'rotate(-45)')
      .style('text-anchor', 'end')
      .attr('dx', '-0.5em')
      .attr('dy', '0.25em');

  g.append('g')
    .attr('class', 'axis y-axis')
    .call(d3.axisLeft(y)
      .ticks(6)
      .tickFormat(d => d3.format(',')(d))
      .tickSizeOuter(0)
    );

  // Y-axis label
  g.append('text')
    .attr('class', 'axis-label')
    .attr('transform', 'rotate(-90)')
    .attr('x', -innerH / 2)
    .attr('y', -38)
    .attr('text-anchor', 'middle')
    .attr('font-family', 'var(--font-sans)')
    .attr('font-size', '10px')
    .attr('fill', 'var(--color-text-muted)')
    .text('kWh / year');

  return { svg, g, x, y, items, innerW, innerH };
}

export function buildLegend(container, hasSolar) {
  const el = d3.select(container);
  el.selectAll('*').remove();

  const legendItems = [
    { label: 'Total consumption', color: 'var(--color-total)' },
    { label: 'Grid consumption', color: 'var(--color-grid)', dashed: true },
  ];
  if (hasSolar) {
    legendItems.push({ label: 'Solar production', color: 'var(--color-solar)' });
  }

  legendItems.forEach(item => {
    const div = el.append('div').attr('class', 'legend-item');
    const swatch = div.append('span')
      .attr('class', 'swatch')
      .style('background', item.color);
    if (item.dashed) {
      swatch.style('background', 'none')
        .style('border-top', `2px dashed ${item.color}`)
        .style('height', '0')
        .style('margin-top', '2px');
    }
    div.append('span').text(item.label);
  });
}
