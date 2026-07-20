(function () {
  if (typeof NGTDashboard === 'undefined') return;

  async function refreshDashboard() {
    const root = document.querySelector('.ngt-dashboard[data-role]');
    if (!root) return;

    const role = root.dataset.role;
    try {
      const res = await fetch(`${NGTDashboard.rest}dashboard/${role}`, {
        headers: { 'X-WP-Nonce': NGTDashboard.nonce },
      });
      if (!res.ok) return;
      const data = await res.json();
      if (!data.stats) return;

      data.stats.forEach((stat) => {
        const card = root.querySelector(`.ngt-stat:nth-child(${data.stats.indexOf(stat) + 1}) .ngt-stat-value`);
        if (card) card.textContent = stat.value;
      });
    } catch (e) {
      /* silent fallback to server-rendered stats */
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    refreshDashboard();
    setInterval(refreshDashboard, 60000);
  });
})();
