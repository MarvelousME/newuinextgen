/* ============================================================
   NextGen Tutors — RTM Chat (Tutors · Staff · Support)
   Maps floating Live Chat to Automation Hub RTM REST + SSE.
   ============================================================ */
(function () {
  "use strict";
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  const FALLBACK_CONTACTS = [
    { id: "support", name: "Support Desk", role: "Support", online: true, avatar: "", lastMsg: "Fluent Support portal available", unread: 0, roomId: 0 },
  ];

  let rooms = [];
  let activeRoomId = 0;
  let sinceId = 0;
  let sse = null;
  let pollTimer = null;

  function cfg() {
    return (window.NGT_WP && window.NGT_WP.rtm) || {};
  }

  function headers() {
    return {
      "Content-Type": "application/json",
      "X-WP-Nonce": cfg().nonce || ""
    };
  }

  function escapeHtml(text) {
    return String(text).replace(/[<>&"]/g, (c) => ({ "<": "&lt;", ">": "&gt;", "&": "&amp;", '"': "&quot;" }[c]));
  }

  function buildChatPanel() {
    if ($("#chat-panel")) return;
    const panel = document.createElement("div");
    panel.className = "chat-panel";
    panel.id = "chat-panel";
    panel.setAttribute("data-testid", "chat-panel");
    panel.innerHTML = `
      <div class="chat-panel__head">
        <div>
          <div class="chat-panel__title">NextGen Chat</div>
          <div class="chat-panel__sub" id="chat-panel-sub">Realtime RTM · Tutors · Staff · Support</div>
        </div>
        <div class="chat-head-actions">
          <button type="button" class="chat-icon-btn chat-icon-btn--video" id="chat-video-btn" aria-label="Video call" title="Video call"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg></button>
          <button type="button" class="chat-icon-btn chat-icon-btn--close" id="chat-close-btn" aria-label="Close chat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
      </div>
      <div class="chat-tabs">
        <button type="button" class="chat-tab is-active" data-tab="contacts">Rooms</button>
        <button type="button" class="chat-tab" data-tab="messages">Messages</button>
      </div>
      <div id="chat-contacts-list" class="chat-contacts"></div>
      <div id="chat-messages-area" class="chat-messages" style="display:none"></div>
      <div class="chat-compose" id="chat-compose" style="display:none">
        <input type="text" id="chat-input" placeholder="Type a message…" aria-label="Message" />
        <button type="button" id="chat-send" aria-label="Send"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></button>
      </div>`;
    document.body.appendChild(panel);
  }

  function buildVideoOverlay() {
    if ($("#video-overlay")) return;
    const ov = document.createElement("div");
    ov.className = "video-overlay";
    ov.id = "video-overlay";
    ov.innerHTML = `
      <div class="video-grid">
        <div class="video-tile" style="background:#0c1a2e;display:flex;align-items:center;justify-content:center">
          <div style="text-align:center;color:rgba(255,255,255,.7)">
            <div style="font-size:18px;font-weight:800;margin-bottom:8px">Jitsi Meeting</div>
            <div style="font-size:12px">Opens in a new window for this RTM room</div>
          </div>
        </div>
      </div>
      <div class="video-controls">
        <button type="button" class="vc-btn vc-btn--end" id="vc-end" title="End call"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M16.5 8.3A8.38 8.38 0 0 0 12 7c-1.57 0-3.04.46-4.29 1.25"/><path d="m22 22-4.69-4.69"/><path d="M2 2l20 20"/></svg></button>
      </div>
      <p style="color:rgba(255,255,255,.4);font-size:11px;font-weight:700;margin-top:16px;text-transform:uppercase;letter-spacing:.08em" id="vc-timer">RTM Video</p>`;
    document.body.appendChild(ov);
  }

  function renderLoginGate() {
    const list = $("#chat-contacts-list");
    if (!list) return;
    const login = cfg().loginUrl || "/wp-login.php";
    list.innerHTML = `
      <div class="chat-contact" style="cursor:default">
        <div style="padding:8px 4px">
          <div class="chat-contact__name">Sign in required</div>
          <div class="chat-contact__last">Realtime RTM chat needs an account.</div>
          <a href="${escapeHtml(login)}" class="btn btn--primary" style="display:inline-flex;margin-top:10px;padding:8px 14px;font-size:12px">Log in to chat</a>
        </div>
      </div>`;
  }

  function renderRooms(listData) {
    const list = $("#chat-contacts-list");
    if (!list) return;
    const items = (listData && listData.length) ? listData : FALLBACK_CONTACTS;
    list.innerHTML = items.map((c) => `
      <div class="chat-contact" data-room="${c.roomId || c.id}" data-title="${escapeHtml(c.name || c.title || "Room")}">
        <div class="chat-contact__av">
          <div style="width:42px;height:42px;border-radius:50%;background:#0f2744;color:#d4f06a;display:flex;align-items:center;justify-content:center;font-weight:900">${escapeHtml((c.name || c.title || "?").charAt(0))}</div>
          <span class="chat-contact__online"></span>
        </div>
        <div style="flex:1;min-width:0">
          <div class="chat-contact__name">${escapeHtml(c.name || c.title || "Room")}</div>
          <div class="chat-contact__role">${escapeHtml(c.slug || c.role || "RTM")}</div>
          <div class="chat-contact__last">${escapeHtml(c.lastMsg || "Tap to open conversation")}</div>
        </div>
      </div>`).join("");
    $$(".chat-contact[data-room]").forEach((el) => {
      el.addEventListener("click", () => openRoom(parseInt(el.dataset.room, 10) || 0, el.dataset.title));
    });
  }

  function appendMessages(messages, replace) {
    const area = $("#chat-messages-area");
    if (!area || !Array.isArray(messages)) return;
    const me = cfg().userId || 0;
    if (replace) area.innerHTML = "";
    messages.forEach((m) => {
      const id = parseInt(m.id, 10) || 0;
      if (id > sinceId) sinceId = id;
      const out = me && String(m.user_id) === String(me);
      const row = document.createElement("div");
      row.className = "chat-msg" + (out ? " chat-msg--out" : "");
      row.innerHTML = `<div class="chat-msg__bubble">${escapeHtml(m.message || "")}</div><div class="chat-msg__time">${escapeHtml(m.display_name || "")} · ${escapeHtml(m.created_at || "")}</div>`;
      area.appendChild(row);
    });
    area.scrollTop = area.scrollHeight;
  }

  async function loadMessages(roomId) {
    const rtm = cfg();
    if (!roomId) return;
    let url = "";
    if (rtm.rest) url = `${rtm.rest.replace(/\/$/, "")}/messages/${roomId}`;
    else if (rtm.messages) url = `${rtm.messages.replace(/\/$/, "")}/${roomId}`;
    if (!url) return;
    const r = await fetch(url, { headers: headers(), credentials: "same-origin" }).catch(() => null);
    if (!r || !r.ok) return;
    const data = await r.json();
    const sorted = Array.isArray(data) ? data.slice().reverse() : [];
    sinceId = 0;
    appendMessages(sorted, true);
  }

  function closeStream() {
    if (sse) { sse.close(); sse = null; }
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
  }

  function openStream(roomId) {
    closeStream();
    const rtm = cfg();
    if (!roomId) return;
    if (!rtm.sse) {
      pollTimer = setInterval(() => loadMessages(roomId), 4000);
      return;
    }
    const url = `${rtm.sse}?room_id=${roomId}&since_id=${sinceId}`;
    try {
      sse = new EventSource(url, { withCredentials: true });
      sse.addEventListener("messages", () => loadMessages(roomId));
      sse.onerror = () => {
        closeStream();
        pollTimer = setInterval(() => loadMessages(roomId), 4000);
      };
    } catch (e) {
      pollTimer = setInterval(() => loadMessages(roomId), 4000);
    }
  }

  async function openRoom(roomId, title) {
    if (!cfg().loggedIn) {
      renderLoginGate();
      return;
    }
    if (!roomId) return;
    activeRoomId = roomId;
    sinceId = 0;
    const area = $("#chat-messages-area");
    const compose = $("#chat-compose");
    const sub = $("#chat-panel-sub");
    if (sub) sub.textContent = (title || "Room") + " · RTM live";
    area.style.display = "flex";
    compose.style.display = "flex";
    $$(".chat-tab").forEach((t) => t.classList.toggle("is-active", t.dataset.tab === "messages"));
    $("#chat-contacts-list").style.display = "none";
    await loadMessages(roomId);
    openStream(roomId);
  }

  async function loadRooms() {
    const rtm = cfg();
    if (!rtm.loggedIn) {
      renderLoginGate();
      return;
    }
    if (!rtm.rooms && !rtm.rest) {
      renderRooms([]);
      return;
    }
    const url = rtm.rooms || `${rtm.rest.replace(/\/$/, "")}/rooms`;
    const res = await fetch(url, { headers: headers(), credentials: "same-origin" }).catch(() => null);
    if (!res || !res.ok) {
      renderRooms([]);
      return;
    }
    const data = await res.json();
    rooms = (Array.isArray(data) ? data : []).map((r) => ({
      roomId: parseInt(r.id, 10),
      id: String(r.id),
      name: r.title || r.slug || ("Room " + r.id),
      slug: r.slug || "rtm",
      title: r.title,
      lastMsg: "Realtime room",
      online: true
    }));
    renderRooms(rooms);
  }

  function initTabs() {
    $$(".chat-tab").forEach((tab) => tab.addEventListener("click", () => {
      $$(".chat-tab").forEach((t) => t.classList.remove("is-active"));
      tab.classList.add("is-active");
      if (tab.dataset.tab === "contacts") {
        $("#chat-contacts-list").style.display = "block";
        $("#chat-messages-area").style.display = "none";
        $("#chat-compose").style.display = "none";
        closeStream();
        activeRoomId = 0;
        if ($("#chat-panel-sub")) $("#chat-panel-sub").textContent = "Realtime RTM · Tutors · Staff · Support";
        loadRooms();
      }
    }));
  }

  function initCompose() {
    const input = $("#chat-input");
    const send = $("#chat-send");
    if (!input || !send) return;
    const doSend = async () => {
      const txt = input.value.trim();
      if (!txt || !activeRoomId) return;
      const rtm = cfg();
      input.value = "";
      appendMessages([{
        id: sinceId + 1,
        user_id: rtm.userId,
        message: txt,
        display_name: rtm.userName || "You",
        created_at: new Date().toLocaleTimeString("en-ZA", { hour: "2-digit", minute: "2-digit" })
      }], false);
      if (!rtm.messages && !rtm.rest) return;
      const url = rtm.messages || `${rtm.rest.replace(/\/$/, "")}/messages`;
      await fetch(url, {
        method: "POST",
        headers: headers(),
        credentials: "same-origin",
        body: JSON.stringify({ room_id: activeRoomId, message: txt })
      }).catch(() => null);
      loadMessages(activeRoomId);
    };
    send.addEventListener("click", doSend);
    input.addEventListener("keydown", (e) => { if (e.key === "Enter") doSend(); });
  }

  function initVideoCall() {
    const overlay = $("#video-overlay");
    const btn = $("#chat-video-btn");
    const endBtn = $("#vc-end");
    if (btn) {
      btn.addEventListener("click", () => {
        if (!activeRoomId) return;
        window.open(`https://meet.jit.si/NextGenTutors-Room-${activeRoomId}`, "_blank", "noopener");
        if (overlay) overlay.classList.add("is-active");
      });
    }
    if (endBtn && overlay) {
      endBtn.addEventListener("click", () => overlay.classList.remove("is-active"));
    }
  }

  function initClose() {
    const closeBtn = $("#chat-close-btn");
    if (closeBtn) {
      closeBtn.addEventListener("click", () => {
        const p = $("#chat-panel");
        if (p) p.classList.remove("is-open");
        closeStream();
      });
    }
  }

  function boot() {
    buildChatPanel();
    buildVideoOverlay();
    const ready = () => {
      initTabs();
      initCompose();
      initClose();
      initVideoCall();
      loadRooms();
      document.addEventListener("ngt:rtm-open", () => {
        const p = $("#chat-panel");
        if (p) p.classList.add("is-open");
        loadRooms();
      });
    };
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", ready);
    else ready();
  }
  boot();
})();
