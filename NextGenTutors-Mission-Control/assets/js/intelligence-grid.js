/**
 * Enterprise virtual-scroll event grid with server paging + pivot.
 */
(function (global) {
  'use strict';

  function EnterpriseGrid(container, apiFn) {
    this.container = container;
    this.apiFn = apiFn;
    this.rowHeight = 36;
    this.buffer = 8;
    this.rows = [];
    this.total = 0;
    this.loading = false;
    this.filters = {};
    this.pivotField = '';
    this.sinceId = 0;
    this.viewport = container.querySelector('.ngtmc-virtual-viewport');
    this.spacer = container.querySelector('.ngtmc-virtual-spacer');
    this.canvas = container.querySelector('.ngtmc-virtual-canvas');
    this.meta = container.querySelector('.ngtmc-virtual-meta');
    if (this.viewport) {
      this.viewport.addEventListener('scroll', this.onScroll.bind(this));
    }
    var pivot = container.closest('.ngtmc-intel-view')?.querySelector('.ngtmc-intel-pivot');
    if (pivot) {
      pivot.addEventListener('change', function (e) {
        this.pivotField = e.target.value;
        this.reset();
        this.loadMore(true);
      }.bind(this));
    }
  }

  function escHtml(value) {
    var d = document.createElement('div');
    d.textContent = value == null ? '' : String(value);
    return d.innerHTML;
  }

  EnterpriseGrid.prototype.reset = function () {
    this.rows = [];
    this.sinceId = 0;
    this.total = 0;
    if (this.spacer) this.spacer.style.height = '0px';
    if (this.canvas) this.canvas.innerHTML = '';
  };

  EnterpriseGrid.prototype.setFilters = function (filters) {
    this.filters = filters || {};
    this.reset();
    return this.loadMore(true);
  };

  EnterpriseGrid.prototype.buildQuery = function () {
    var q = Object.assign({ per_page: 50 }, this.filters);
    if (this.sinceId > 0) q.since_id = this.sinceId;
    return '?' + new URLSearchParams(q).toString();
  };

  EnterpriseGrid.prototype.loadMore = function (replace) {
    if (this.loading) return Promise.resolve();
    this.loading = true;
    return this.apiFn(this.buildQuery()).then(function (res) {
      this.loading = false;
      this.total = res.total || 0;
      var batch = res.rows || [];
      if (replace) this.rows = batch;
      else this.rows = this.rows.concat(batch);
      if (batch.length) {
        this.sinceId = batch[batch.length - 1].id;
      }
      this.render();
      if (this.meta) {
        this.meta.textContent = this.rows.length + ' loaded / ' + this.total + ' total';
      }
    }.bind(this)).catch(function () {
      this.loading = false;
    }.bind(this));
  };

  EnterpriseGrid.prototype.onScroll = function () {
    if (!this.viewport) return;
    var st = this.viewport.scrollTop;
    var vh = this.viewport.clientHeight;
    var totalH = this.rows.length * this.rowHeight;
    if (st + vh >= totalH - this.rowHeight * 3 && this.rows.length < this.total) {
      this.loadMore(false);
    }
    this.paintWindow(st, vh);
  };

  EnterpriseGrid.prototype.render = function () {
    if (!this.spacer) return;
    var data = this.pivotField ? this.pivotRows(this.rows, this.pivotField) : this.rows;
    this._renderData = data;
    this.spacer.style.height = (data.length * this.rowHeight) + 'px';
    this.paintWindow(this.viewport ? this.viewport.scrollTop : 0, this.viewport ? this.viewport.clientHeight : 400);
  };

  EnterpriseGrid.prototype.pivotRows = function (rows, field) {
    var map = {};
    rows.forEach(function (r) {
      var k = r[field] || 'unknown';
      map[k] = map[k] || { _key: k, count: 0, severity: r.severity, plugin_slug: r.plugin_slug };
      map[k].count++;
    });
    return Object.keys(map).map(function (k) {
      return {
        id: k,
        recorded_at: '—',
        event_key: 'pivot:' + field,
        plugin_slug: map[k].plugin_slug || '',
        module: map[k]._key,
        severity: map[k].severity || 'info',
        message: map[k].count + ' events',
      };
    });
  };

  EnterpriseGrid.prototype.paintWindow = function (scrollTop, viewHeight) {
    if (!this.canvas) return;
    var data = this._renderData || this.rows;
    var start = Math.max(0, Math.floor(scrollTop / this.rowHeight) - this.buffer);
    var end = Math.min(data.length, start + Math.ceil(viewHeight / this.rowHeight) + this.buffer * 2);
    var html = '';
    for (var i = start; i < end; i++) {
      var r = data[i];
      var top = i * this.rowHeight;
      html += '<div class="ngtmc-virtual-row" style="top:' + top + 'px" data-event-id="' + escHtml(r.id) + '">' +
        '<span>' + escHtml(r.recorded_at || '') + '</span>' +
        '<code>' + escHtml(r.event_key || '') + '</code>' +
        '<span>' + escHtml(r.plugin_slug || '') + '</span>' +
        '<span>' + escHtml(r.module || '') + '</span>' +
        '<span class="ngtmc-sev ngtmc-sev--' + escHtml(r.severity || '') + '">' + escHtml(r.severity || '') + '</span>' +
        '<span>' + escHtml(r.message || '') + '</span></div>';
    }
    this.canvas.innerHTML = html;
    if (this.canvas) this.canvas.style.top = (start * this.rowHeight) + 'px';
  };

  global.NGTMCEnterpriseGrid = EnterpriseGrid;
})(window);
