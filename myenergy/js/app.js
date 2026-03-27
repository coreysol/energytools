(function () {
  'use strict';

  /* ── Chart ─────────────────────────────────────────────────── */

  var MARGIN = { top: 20, right: 20, bottom: 48, left: 55 };

  function aggregateToYears(data) {
    var groups = new Map();

    for (var i = 0; i < data.length; i++) {
      var d = data[i];
      var year = d.year_month.slice(0, 4);
      if (!groups.has(year)) {
        groups.set(year, { year: +year, grid_vals: [], solar_vals: [], total_vals: [] });
      }
      var g = groups.get(year);
      g.grid_vals.push(d.grid_kwh);
      g.total_vals.push(d.grid_kwh + (d.solar_kwh != null ? d.solar_kwh : 0));
      if (d.solar_kwh != null) g.solar_vals.push(d.solar_kwh);
    }

    var result = [];
    groups.forEach(function (g) {
      if (g.grid_vals.length < 6) return;
      var sum = function (arr) { var s = 0; for (var j = 0; j < arr.length; j++) s += arr[j]; return s; };
      result.push({
        year_month: g.year + '-07',
        grid_kwh: Math.round(sum(g.grid_vals)),
        solar_kwh: g.solar_vals.length > 0 ? Math.round(sum(g.solar_vals)) : null,
        total_kwh: Math.round(sum(g.total_vals)),
      });
    });

    result.sort(function (a, b) { return a.year_month < b.year_month ? -1 : 1; });
    return result;
  }

  function createChart(container, data) {
    var wrapper = d3.select(container);
    wrapper.selectAll('*').remove();

    var rect = wrapper.node().getBoundingClientRect();
    var width = rect.width;
    var height = rect.height;
    var innerW = width - MARGIN.left - MARGIN.right;
    var innerH = height - MARGIN.top - MARGIN.bottom;

    var svg = wrapper.append('svg')
      .attr('viewBox', '0 0 ' + width + ' ' + height)
      .attr('preserveAspectRatio', 'xMidYMid meet');

    var g = svg.append('g')
      .attr('transform', 'translate(' + MARGIN.left + ',' + MARGIN.top + ')');

    var parseYM = d3.timeParse('%Y-%m');
    var yearly = aggregateToYears(data);
    var items = yearly.map(function (d) {
      return Object.assign({}, d, { date: parseYM(d.year_month) });
    });

    var x = d3.scaleTime()
      .domain(d3.extent(items, function (d) { return d.date; }))
      .range([0, innerW]);

    var yMax = d3.max(items, function (d) { return Math.max(d.total_kwh, d.solar_kwh || 0); });
    var y = d3.scaleLinear()
      .domain([0, yMax * 1.08])
      .nice()
      .range([innerH, 0]);

    g.append('g')
      .attr('class', 'grid-line')
      .call(d3.axisLeft(y).ticks(6).tickSize(-innerW).tickFormat(''))
      .call(function (sel) { sel.select('.domain').remove(); });

    var totalArea = d3.area()
      .x(function (d) { return x(d.date); })
      .y0(y(0))
      .y1(function (d) { return y(d.total_kwh); })
      .curve(d3.curveMonotoneX);

    g.append('path').datum(items)
      .attr('class', 'area-total')
      .attr('d', totalArea)
      .attr('fill', 'var(--color-total-area)')
      .attr('stroke', 'none');

    var totalLine = d3.line()
      .x(function (d) { return x(d.date); })
      .y(function (d) { return y(d.total_kwh); })
      .curve(d3.curveMonotoneX);

    g.append('path').datum(items)
      .attr('class', 'line-total')
      .attr('d', totalLine)
      .attr('fill', 'none')
      .attr('stroke', 'var(--color-total)')
      .attr('stroke-width', 2);

    var gridLine = d3.line()
      .x(function (d) { return x(d.date); })
      .y(function (d) { return y(Math.max(d.grid_kwh, 0)); })
      .curve(d3.curveMonotoneX);

    g.append('path').datum(items)
      .attr('class', 'line-grid')
      .attr('d', gridLine)
      .attr('fill', 'none')
      .attr('stroke', 'var(--color-grid)')
      .attr('stroke-width', 3)
      .attr('stroke-dasharray', '6 3');

    var solarItems = items.filter(function (d) { return d.solar_kwh != null; });

    if (solarItems.length > 0) {
      var solarArea = d3.area()
        .x(function (d) { return x(d.date); })
        .y0(y(0))
        .y1(function (d) { return y(d.solar_kwh); })
        .curve(d3.curveMonotoneX);

      g.append('path').datum(solarItems)
        .attr('class', 'area-solar')
        .attr('d', solarArea)
        .attr('fill', 'var(--color-solar-area)')
        .attr('stroke', 'none');

      var solarLine = d3.line()
        .x(function (d) { return x(d.date); })
        .y(function (d) { return y(d.solar_kwh); })
        .curve(d3.curveMonotoneX);

      g.append('path').datum(solarItems)
        .attr('class', 'line-solar')
        .attr('d', solarLine)
        .attr('fill', 'none')
        .attr('stroke', 'var(--color-solar)')
        .attr('stroke-width', 2);
    }

    g.append('g')
      .attr('class', 'axis x-axis')
      .attr('transform', 'translate(0,' + innerH + ')')
      .call(d3.axisBottom(x)
        .ticks(d3.timeYear.every(1))
        .tickFormat(function (d) { return d.getFullYear() % 2 !== 0 ? d3.timeFormat("'%y")(d) : ''; })
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
        .tickFormat(function (d) { return d3.format(',')(d); })
        .tickSizeOuter(0)
      );

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

    return { svg: svg, g: g, x: x, y: y, items: items, innerW: innerW, innerH: innerH };
  }

  function buildLegend(container, hasSolar) {
    var el = d3.select(container);
    el.selectAll('*').remove();

    var legendItems = [
      { label: 'Total consumption', color: 'var(--color-total)' },
      { label: 'Grid consumption', color: 'var(--color-grid)', dashed: true },
    ];
    if (hasSolar) {
      legendItems.push({ label: 'Solar production', color: 'var(--color-solar)' });
    }

    legendItems.forEach(function (item) {
      var div = el.append('div').attr('class', 'legend-item');
      var swatch = div.append('span')
        .attr('class', 'swatch')
        .style('background', item.color);
      if (item.dashed) {
        swatch.style('background', 'none')
          .style('border-top', '2px dashed ' + item.color)
          .style('height', '0')
          .style('margin-top', '2px');
      }
      div.append('span').text(item.label);
    });
  }

  /* ── Annotations ───────────────────────────────────────────── */

  var parseYM = d3.timeParse('%Y-%m');

  function drawAnnotations(chartState, events) {
    var g = chartState.g;
    var x = chartState.x;
    var innerH = chartState.innerH;

    g.selectAll('.event-marker').remove();
    g.selectAll('.highlight-band').remove();

    events.forEach(function (ev) {
      var startDate = parseYM(ev.anchor);
      if (!startDate) return;

      var endDate = ev.end ? parseYM(ev.end) : null;

      if (endDate) {
        g.append('rect')
          .attr('class', 'highlight-band')
          .attr('x', x(startDate))
          .attr('y', 0)
          .attr('width', Math.max(x(endDate) - x(startDate), 4))
          .attr('height', innerH)
          .attr('fill', 'var(--color-band)');
      }

      var markerG = g.append('g').attr('class', 'event-marker');
      markerG.append('line')
        .attr('x1', x(startDate))
        .attr('x2', x(startDate))
        .attr('y1', 0)
        .attr('y2', innerH);
    });
  }

  function highlightTimeWindow(chartState, anchor, end) {
    var g = chartState.g;
    var x = chartState.x;
    var innerH = chartState.innerH;

    g.selectAll('.active-highlight').remove();

    var startDate = parseYM(anchor);
    if (!startDate) return;

    var endDate = end ? parseYM(end) : d3.timeMonth.offset(startDate, 1);

    g.insert('rect', ':first-child')
      .attr('class', 'active-highlight')
      .attr('x', x(startDate))
      .attr('y', 0)
      .attr('width', Math.max(x(endDate) - x(startDate), 12))
      .attr('height', innerH)
      .attr('fill', 'var(--color-active-highlight)')
      .attr('rx', 3);
  }

  /* ── Scroll ────────────────────────────────────────────────── */

  function formatDate(ym) {
    var parts = ym.split('-');
    var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return months[parseInt(parts[1], 10) - 1] + ' ' + parts[0];
  }

  function formatSoftDate(ym) {
    var m = parseInt(ym.split('-')[1], 10);
    var y = ym.split('-')[0];
    if (m <= 3) return 'Early ' + y;
    if (m <= 6) return 'Spring ' + y;
    if (m <= 9) return 'Summer ' + y;
    return 'Late ' + y;
  }

  function initScroll(chartState, events) {
    var stepsContainer = document.getElementById('scroll-steps');
    stepsContainer.innerHTML = '';

    var heroImg = document.createElement('div');
    heroImg.className = 'step-hero-image';
    heroImg.innerHTML = '<img src="images/front of house.jpg" alt="Our home">';
    stepsContainer.appendChild(heroImg);

    events.forEach(function (ev, i) {
      var step = document.createElement('div');
      step.className = 'step';
      step.dataset.index = i;
      step.dataset.anchor = ev.anchor;
      if (ev.end) step.dataset.end = ev.end;

      var startLabel = ev.sensitivity === 'soft'
        ? formatSoftDate(ev.anchor)
        : formatDate(ev.anchor);
      var dateLabel = ev.end
        ? startLabel + ' – ' + formatDate(ev.end)
        : startLabel;

      var imageHtml = '';
      if (ev.image) {
        imageHtml = '<div class="step-image"><img src="images/' + ev.image + '" alt="' + ev.title + '"></div>';
      }

      step.innerHTML =
        '<div class="step-content">' +
          '<h3>' + ev.title + '</h3>' +
          '<p>' + ev.body + '</p>' +
          '<div class="step-date">' + dateLabel + '</div>' +
        '</div>' +
        imageHtml;
      stepsContainer.appendChild(step);
    });

    var scroller = scrollama();

    scroller
      .setup({
        step: '#scroll-steps .step',
        offset: 0.5,
        debug: false,
      })
      .onStepEnter(function (response) {
        document.querySelectorAll('.step').forEach(function (s) { s.classList.remove('is-active'); });
        response.element.classList.add('is-active');
        var anchor = response.element.dataset.anchor;
        var end = response.element.dataset.end || null;
        highlightTimeWindow(chartState, anchor, end);
      });

    window.addEventListener('resize', scroller.resize);
    return scroller;
  }

  /* ── Main ──────────────────────────────────────────────────── */

  function loadJSON(path) {
    return fetch(path).then(function (resp) {
      if (!resp.ok) throw new Error('Failed to load ' + path + ': ' + resp.status);
      return resp.json();
    });
  }

  function renderStats(data) {
    var container = document.getElementById('stats');
    var preMeasures = data.filter(function (d) { return d.year_month < '2011-01'; });
    var postMeasures = data.filter(function (d) { return d.year_month >= '2011-01'; });

    var avgPre = preMeasures.reduce(function (s, d) { return s + d.grid_kwh; }, 0) / preMeasures.length;
    var avgPost = postMeasures.reduce(function (s, d) { return s + d.grid_kwh; }, 0) / postMeasures.length;

    var totalSolar = data
      .filter(function (d) { return d.solar_kwh != null; })
      .reduce(function (s, d) { return s + d.solar_kwh; }, 0);

    var stats = [
      { value: Math.round(avgPre).toLocaleString(), label: 'Avg. monthly grid consumption before energy efficiency and solar' },
      { value: Math.round(avgPost).toLocaleString(), label: 'Avg. monthly grid consumption after energy efficiency and solar' },
      { value: Math.round(totalSolar).toLocaleString(), label: 'Total solar kWh produced' },
    ];

    container.innerHTML = stats.map(function (s) {
      return '<div class="stat-card">' +
        '<div class="stat-value">' + s.value + '</div>' +
        '<div class="stat-label">' + s.label + '</div>' +
      '</div>';
    }).join('');
  }

  function main() {
    Promise.all([
      loadJSON('data/energy_story.json'),
      loadJSON('data/events.json'),
    ]).then(function (results) {
      var data = results[0];
      var events = results[1];

      var hasSolar = data.some(function (d) { return d.solar_kwh != null; });
      var chartState = createChart('#chart', data);
      buildLegend('#chart-legend', hasSolar);
      drawAnnotations(chartState, events);
      initScroll(chartState, events);
      renderStats(data);

      var resizeTimer;
      window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
          var newState = createChart('#chart', data);
          Object.assign(chartState, newState);
          buildLegend('#chart-legend', hasSolar);
          drawAnnotations(chartState, events);
        }, 200);
      });
    }).catch(function (err) {
      console.error('Failed to initialize:', err);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', main);
  } else {
    main();
  }

})();
