(function () {
  let room = 0;
  let sinceId = 0;
  let sse = null;
  let pollTimer = null;

  const $ = (s) => document.querySelector(s);

  function headers() {
    return { 'Content-Type': 'application/json', 'X-WP-Nonce': NGTRTM.nonce };
  }

  function escapeHtml(text) {
    return String(text).replace(/[<>&]/g, (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;' }[c]));
  }

  function renderMessages(data) {
    const box = $('#ngt-messages');
    if (!box || !Array.isArray(data)) return;
    data.forEach((m) => {
      if (m.id > sinceId) sinceId = m.id;
    });
    box.innerHTML = data
      .slice()
      .reverse()
      .map(
        (m) =>
          `<div class="ngt-msg ${m.message_type === 'system' ? 'system' : ''}"><small>${escapeHtml(
            m.display_name || 'System'
          )} · ${escapeHtml(m.created_at || '')}</small><div>${escapeHtml(m.message)}</div></div>`
      )
      .join('');
    box.scrollTop = box.scrollHeight;
  }

  async function load() {
    if (!room) return;
    const r = await fetch(`${NGTRTM.rest}/messages/${room}`, { headers: headers() });
    if (!r.ok) return;
    renderMessages(await r.json());
  }

  function closeStream() {
    if (sse) {
      sse.close();
      sse = null;
    }
    if (pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
    }
  }

  function openStream() {
    closeStream();
    if (!room || !NGTRTM.sse) {
      pollTimer = setInterval(load, 4000);
      return;
    }

    const url = `${NGTRTM.sse}?room_id=${room}&since_id=${sinceId}`;
    sse = new EventSource(url, { withCredentials: true });

    sse.addEventListener('messages', (e) => {
      try {
        const incoming = JSON.parse(e.data);
        load().then(() => {
          if (Array.isArray(incoming) && incoming.length) {
            sinceId = Math.max(sinceId, incoming[incoming.length - 1].id || 0);
          }
        });
      } catch (err) {
        load();
      }
    });

    sse.onerror = () => {
      closeStream();
      pollTimer = setInterval(load, 4000);
    };

    load();
  }

  function setRoom(id, title) {
    room = parseInt(id, 10);
    sinceId = 0;
    const titleEl = $('#ngt-room-title');
    if (titleEl) titleEl.textContent = title || 'Communication Hub';
    openStream();
  }

  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('ngt-room')) {
      setRoom(e.target.dataset.room, e.target.textContent);
    }
    if (e.target.id === 'ngt-video') {
      if (!room) return;
      window.open(`https://meet.jit.si/NextGenTutors-Room-${room}`, '_blank', 'noopener');
    }
  });

  document.addEventListener('submit', async (e) => {
    if (e.target.id !== 'ngt-message-form') return;
    e.preventDefault();
    const input = $('#ngt-message');
    const msg = input.value.trim();
    if (!msg || !room) return;
    input.value = '';
    await fetch(`${NGTRTM.rest}/messages`, {
      method: 'POST',
      headers: headers(),
      body: JSON.stringify({ room_id: room, message: msg }),
    });
    load();
  });

  document.addEventListener('DOMContentLoaded', () => {
    const wrap = document.querySelector('.ngt-rtm');
    if (wrap && wrap.dataset.defaultRoom > 0) {
      const first = document.querySelector('.ngt-room');
      setRoom(wrap.dataset.defaultRoom, first ? first.textContent : 'Communication Hub');
    }
  });
})();
