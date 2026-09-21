/* ============================================================
   NextGen Tutors — Grid Export (CSV + Print-to-PDF)
   Auto-wires to every .dash-table and .cmp-table on the page.
   ============================================================ */
(function () {
  "use strict";
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  function tableToCSV(table) {
    const rows = $$("tr", table);
    return rows.map((row) =>
      $$("th,td", row).map((cell) => `"${cell.textContent.replace(/"/g, '""').trim()}"`).join(",")
    ).join("\n");
  }

  function downloadCSV(csv, filename) {
    const blob = new Blob(["\ufeff" + csv], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url; a.download = filename + ".csv"; a.click();
    setTimeout(() => URL.revokeObjectURL(url), 2000);
  }

  function printTable(table, title) {
    const win = window.open("", "_blank", "width=900,height=700");
    win.document.write(`
      <!DOCTYPE html><html><head><title>${title}</title>
      <style>
        body { font-family: sans-serif; padding: 30px; color: #1e293b; }
        h2 { color: #092746; text-transform: uppercase; letter-spacing: .06em; font-size: 18px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #092746; color: #fff; padding: 11px 14px; text-align: left; }
        td { padding: 10px 14px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) td { background: #f8fafc; }
        .footer { margin-top: 24px; font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; }
      </style></head><body>
      <h2>${title}</h2>
      ${table.outerHTML}
      <div class="footer">NextGen Tutors · Exported ${new Date().toLocaleDateString("en-ZA")} · Confidential</div>
      </body></html>`);
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); }, 400);
  }

  function addExportBar(table) {
    const panel = table.closest(".panel");
    if (!panel) return;
    const head = $(".panel__h", panel);
    if (!head) return;
    // Avoid double-injecting
    if ($(".export-bar", head)) return;
    const title = $("h2", head)?.textContent.trim() || "Export";
    const bar = document.createElement("div");
    bar.className = "export-bar";
    bar.innerHTML = `
      <button class="export-btn export-btn--pdf" title="Print / Save as PDF">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="15" y2="17"/></svg> PDF
      </button>
      <button class="export-btn export-btn--csv" title="Export as Excel / CSV">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/></svg> Excel
      </button>`;
    head.appendChild(bar);
    $(".export-btn--pdf", bar).addEventListener("click", () => printTable(table, title));
    $(".export-btn--csv", bar).addEventListener("click", () => downloadCSV(tableToCSV(table), title.toLowerCase().replace(/\s+/g, "-")));
  }

  function init() {
    $$(".dash-table, .cmp-table").forEach(addExportBar);
    // Also watch for dynamically added tables (tutor carousel, etc.)
    if (window.MutationObserver) {
      const obs = new MutationObserver((mutations) => {
        mutations.forEach((m) => m.addedNodes.forEach((n) => {
          if (n.nodeType === 1) {
            if (n.matches?.(".dash-table,.cmp-table")) addExportBar(n);
            $$(".dash-table,.cmp-table", n).forEach(addExportBar);
          }
        }));
      });
      obs.observe(document.body, { childList: true, subtree: true });
    }
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();
