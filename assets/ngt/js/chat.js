/* ============================================================
   NextGen Tutors — RTM Chat (Tutors · Staff · Support)
   Designed for Agora RTM SDK or Firebase Realtime integration.
   ============================================================ */
(function () {
  "use strict";
  const $ = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));

  const CONTACTS = [
    { id: "admin", name: "Admin Team", role: "Support", online: true, avatar: "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=200&h=200", lastMsg: "Session logs updated ✓", unread: 2 },
    { id: "karabo", name: "Karabo Molefe", role: "Tutor", online: true, avatar: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=200&h=200", lastMsg: "Available Mon-Thu 9am–5pm", unread: 0 },
    { id: "lindiwe", name: "Lindiwe Nkosi", role: "Tutor", online: true, avatar: "https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=200&h=200", lastMsg: "Can we reschedule Friday?", unread: 1 },
    { id: "support", name: "Support Desk", role: "FluentSupport", online: true, avatar: "https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=200&h=200", lastMsg: "Ticket #8843 resolved", unread: 0 },
  ];

  const DEMO_MESSAGES = {
    admin: [
      { out: false, text: "Monthly payout run is scheduled for 1 July — R58,200 total.", time: "09:14" },
      { out: true, text: "Thanks, I'll notify all tutors.", time: "09:16" },
      { out: false, text: "Session logs updated ✓", time: "09:21" },
    ],
    karabo: [
      { out: false, text: "Hi! I have a slot open Tuesday 14:00 if you need a replacement.", time: "08:30" },
      { out: true, text: "Great, I'll book that in. Naledi needs extra Maths prep.", time: "08:45" },
    ],
  };

  let activeContact = null;

  function buildChatPanel() {
    const panel = document.createElement("div");
    panel.className = "chat-panel"; panel.id = "chat-panel";
    panel.innerHTML = `
      <div class="chat-panel__head">
        <div>
          <div class="chat-panel__title">NextGen Chat</div>
          <div class="chat-panel__sub" id="chat-panel-sub">Tutors · Staff · Support — RTM Only</div>
        </div>
        <div class="chat-head-actions">
          <button class="chat-icon-btn chat-icon-btn--call" id="chat-audio-btn" aria-label="Audio call" title="Audio call"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.07 11.5 19.79 19.79 0 0 1 1 2.88 2 2 0 0 1 2.96 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 8.91A16 16 0 0 0 15.91 17.91l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 24 18.92z"/></svg></button>
          <button class="chat-icon-btn chat-icon-btn--video" id="chat-video-btn" aria-label="Video call" title="Video call"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg></button>
          <button class="chat-icon-btn chat-icon-btn--close" id="chat-close-btn" aria-label="Close chat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
      </div>
      <div class="chat-tabs">
        <button class="chat-tab is-active" data-tab="contacts">Contacts</button>
        <button class="chat-tab" data-tab="messages">Messages</button>
      </div>
      <div id="chat-contacts-list" class="chat-contacts"></div>
      <div id="chat-messages-area" class="chat-messages" style="display:none"></div>
      <div class="chat-compose" id="chat-compose" style="display:none">
        <input type="text" id="chat-input" placeholder="Type a message…" aria-label="Message" />
        <button id="chat-send" aria-label="Send"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg></button>
      </div>`;
    document.body.appendChild(panel);
  }

  function buildVideoOverlay() {
    const ov = document.createElement("div");
    ov.className = "video-overlay"; ov.id = "video-overlay";
    ov.innerHTML = `
      <div class="video-grid">
        <div class="video-tile"><img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=600" alt="Karabo" referrerpolicy="no-referrer" /><div class="video-tile__label"><i data-lucide="mic"></i> Karabo Molefe</div></div>
        <div class="video-tile" style="background:#0c1a2e;display:flex;align-items:center;justify-content:center"><div style="text-align:center;color:rgba(255,255,255,.5)"><div style="font-size:40px;margin-bottom:8px">🎓</div><div style="font-size:13px;font-weight:700">You</div></div><div class="video-tile__label" style="bottom:12px;left:14px;position:absolute"><i data-lucide="mic"></i> You</div></div>
      </div>
      <div class="video-controls">
        <button class="vc-btn vc-btn--mute" id="vc-mute" title="Mute"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg></button>
        <button class="vc-btn vc-btn--video" id="vc-cam" title="Camera off"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg></button>
        <button class="vc-btn vc-btn--screen" id="vc-screen" title="Screen share"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg></button>
        <button class="vc-btn vc-btn--end" id="vc-end" title="End call"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M16.5 8.3A8.38 8.38 0 0 0 12 7c-1.57 0-3.04.46-4.29 1.25"/><path d="m22 22-4.69-4.69"/><path d="M2 2l20 20"/><path d="M10.68 13.31a16 16 0 0 0 2.29 1.9L14 14l2 2a10 10 0 0 0 2.09 1.1l2-2a15.87 15.87 0 0 1-2.35-3.05L16 10.5V8.6A8.59 8.59 0 0 0 12 8C9.8 8 7.8 8.74 6.25 10L4 12a10 10 0 0 0 1.1 2.09l2 2"/></svg></button>
      </div>
      <p style="color:rgba(255,255,255,.4);font-size:11px;font-weight:700;margin-top:16px;text-transform:uppercase;letter-spacing:.08em" id="vc-timer">00:00</p>`;
    document.body.appendChild(ov);
  }

  function renderContacts() {
    const list = $("#chat-contacts-list");
    if (!list) return;
    list.innerHTML = CONTACTS.map((c) => `
      <div class="chat-contact" data-contact="${c.id}">
        <div class="chat-contact__av">
          <img src="${c.avatar}" alt="${c.name}" referrerpolicy="no-referrer" />
          <span class="chat-contact__online${c.online ? "" : " away"}"></span>
        </div>
        <div style="flex:1;min-width:0">
          <div class="chat-contact__name">${c.name} ${c.unread ? `<span class="chat-badge">${c.unread}</span>` : ""}</div>
          <div class="chat-contact__role">${c.role}</div>
          <div class="chat-contact__last">${c.lastMsg}</div>
        </div>
        <div class="chat-contact__time">Now</div>
      </div>`).join("");
    $$(".chat-contact").forEach((el) => el.addEventListener("click", () => openConversation(el.dataset.contact)));
  }

  function openConversation(id) {
    const contact = CONTACTS.find((c) => c.id === id);
    if (!contact) return;
    activeContact = id;
    const msgs = DEMO_MESSAGES[id] || [];
    const area = $("#chat-messages-area");
    const compose = $("#chat-compose");
    const sub = $("#chat-panel-sub");
    if (sub) sub.textContent = contact.name + " · " + contact.role;
    area.style.display = "flex";
    compose.style.display = "flex";
    // switch tab
    $$(".chat-tab").forEach((t) => t.classList.toggle("is-active", t.dataset.tab === "messages"));
    $("#chat-contacts-list").style.display = "none";
    area.innerHTML = msgs.map((m) => `
      <div class="chat-msg${m.out ? " chat-msg--out" : ""}">
        <div class="chat-msg__bubble">${m.text}</div>
        <div class="chat-msg__time">${m.time}</div>
      </div>`).join("");
    area.scrollTop = area.scrollHeight;
  }

  function initTabs() {
    $$(".chat-tab").forEach((tab) => tab.addEventListener("click", () => {
      $$(".chat-tab").forEach((t) => t.classList.remove("is-active"));
      tab.classList.add("is-active");
      if (tab.dataset.tab === "contacts") {
        $("#chat-contacts-list").style.display = "block";
        $("#chat-messages-area").style.display = "none";
        $("#chat-compose").style.display = "none";
        activeContact = null;
        if ($("#chat-panel-sub")) $("#chat-panel-sub").textContent = "Tutors · Staff · Support — RTM Only";
      }
    }));
  }

  function initCompose() {
    const input = $("#chat-input"), send = $("#chat-send");
    if (!input) return;
    const doSend = () => {
      const txt = input.value.trim();
      if (!txt) return;
      const area = $("#chat-messages-area");
      const msg = document.createElement("div");
      msg.className = "chat-msg chat-msg--out";
      msg.innerHTML = `<div class="chat-msg__bubble">${txt}</div><div class="chat-msg__time">${new Date().toLocaleTimeString("en-ZA",{hour:"2-digit",minute:"2-digit"})}</div>`;
      area.appendChild(msg);
      area.scrollTop = area.scrollHeight;
      input.value = "";
      // Simulate reply
      setTimeout(() => {
        const reply = document.createElement("div");
        reply.className = "chat-msg";
        reply.innerHTML = `<div class="chat-msg__bubble">Got it, thank you! I'll check and get back to you shortly.</div><div class="chat-msg__time">${new Date().toLocaleTimeString("en-ZA",{hour:"2-digit",minute:"2-digit"})}</div>`;
        area.appendChild(reply);
        area.scrollTop = area.scrollHeight;
      }, 1400);
    };
    send.addEventListener("click", doSend);
    input.addEventListener("keydown", (e) => { if (e.key === "Enter") doSend(); });
  }

  function initVideoCall() {
    let timerInterval, elapsed = 0;
    const overlay = $("#video-overlay");
    const startCall = () => {
      overlay.classList.add("is-active");
      document.body.style.overflow = "hidden";
      elapsed = 0;
      timerInterval = setInterval(() => {
        elapsed++;
        const m = String(Math.floor(elapsed/60)).padStart(2,"0");
        const s = String(elapsed%60).padStart(2,"0");
        const el = $("#vc-timer");
        if (el) el.textContent = `${m}:${s}`;
      }, 1000);
      if (window.lucide) lucide.createIcons();
    };
    const endCall = () => {
      overlay.classList.remove("is-active");
      document.body.style.overflow = "";
      clearInterval(timerInterval);
    };
    const btn = $("#chat-video-btn");
    if (btn) btn.addEventListener("click", startCall);
    const endBtn = $("#vc-end");
    if (endBtn) endBtn.addEventListener("click", endCall);
    overlay.addEventListener("keydown", (e) => { if (e.key === "Escape") endCall(); });
    const muteBtn = $("#vc-mute");
    if (muteBtn) muteBtn.addEventListener("click", () => muteBtn.style.background = muteBtn.style.background ? "" : "rgba(239,68,68,.7)");
  }

  function initClose() {
    const closeBtn = $("#chat-close-btn");
    if (closeBtn) closeBtn.addEventListener("click", () => { const p = $("#chat-panel"); if (p) p.classList.remove("is-open"); });
  }

  function boot() {
    buildChatPanel();
    buildVideoOverlay();
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", () => { renderContacts(); initTabs(); initCompose(); initClose(); initVideoCall(); if (window.lucide) lucide.createIcons(); });
    } else {
      renderContacts(); initTabs(); initCompose(); initClose(); initVideoCall();
      if (window.lucide) lucide.createIcons();
    }
  }
  boot();
})();
