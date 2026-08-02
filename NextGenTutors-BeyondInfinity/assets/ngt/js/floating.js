/* ============================================================
   NextGen Tutors — Floating Dock (WA + Chat + Support + Top)
   Wired to Fluent Support tickets + Hub RTM chat config.
   ============================================================ */
(function () {
  "use strict";
  const $ = (s, c) => (c || document).querySelector(s);
  const cfg = () => window.NGT_WP || {};

  function injectCss() {
    if (window.NGT_SKIP_CSS_INJECT) return;
    if (document.querySelector('link[href*="floating.css"]')) return;
    const link = document.createElement("link");
    link.rel = "stylesheet";
    link.href = (cfg().assetsUrl || "assets/") + "css/floating.css";
    document.head.appendChild(link);
  }

  function supportUrl() {
    const s = cfg().support || {};
    return s.portalUrl || cfg().contactUrl || "/support/";
  }

  function waHref() {
    const num = (cfg().waNumber || "27813340625").toString().replace(/\D+/g, "");
    const text = encodeURIComponent("Hi NextGen Tutors, I need help");
    return "https://wa.me/" + num + "?text=" + text;
  }

  function buildDock() {
    if ($("#float-dock")) return;
    const dock = document.createElement("div");
    dock.className = "float-dock";
    dock.id = "float-dock";
    dock.setAttribute("data-testid", "float-dock");
    dock.innerHTML = `
      <button type="button" class="fdock-btn fdock-btn--top is-visible" id="back-to-top" aria-label="Back to top" title="Back to top">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"><path d="M18 15l-6-6-6 6"/></svg>
        <span class="fdock-tooltip">Back to top</span>
      </button>
      <button type="button" class="fdock-btn fdock-btn--match has-pulse" id="match-dock-btn" aria-label="Find a tutor match" title="Match Tutor">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 2l2.4 7.2H22l-6 4.6 2.3 7.2L12 16.8 5.7 21l2.3-7.2-6-4.6h7.6z"/></svg>
        <span class="fdock-tooltip">Match Tutor</span>
      </button>
      <button type="button" class="fdock-btn fdock-btn--support" id="support-dock-btn" aria-label="Support" title="Support Centre" data-testid="support-dock-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3z"/><path d="M3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
        <span class="fdock-tooltip">Support</span>
      </button>
      <button type="button" class="fdock-btn fdock-btn--livechat has-unread" id="chat-dock-btn" aria-label="Live Chat" title="Live Chat" data-testid="chat-dock-btn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        <span class="fdock-tooltip">Live Chat</span>
      </button>
      <a class="fdock-btn fdock-btn--wa" href="${waHref()}" target="_blank" rel="noopener" aria-label="WhatsApp" title="WhatsApp" data-testid="wa-dock-btn">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
        <span class="fdock-tooltip">WhatsApp</span>
      </a>`;
    document.body.appendChild(dock);
  }

  function buildSupportPanel() {
    if ($("#support-panel")) return;
    const el = document.createElement("div");
    el.className = "support-panel";
    el.id = "support-panel";
    el.setAttribute("data-testid", "support-panel");
    el.innerHTML = `
      <div class="support-panel__head">
        <div>
          <div class="support-panel__title">Support Centre</div>
          <div class="support-panel__sub">Fluent Support · NextGen Tutors</div>
        </div>
        <button type="button" class="chat-icon-btn chat-icon-btn--close" id="support-close" aria-label="Close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="support-panel__body" id="sp-actions-view">
        <button type="button" class="support-action" id="sp-ticket-btn">
          <div class="support-action__ico" style="background:#dbeafe;color:#1d4ed8">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"/></svg>
          </div>
          <div>
            <div class="support-action__title">Create a Ticket</div>
            <div class="support-action__desc">Opens in Fluent Support — we respond within 2 hours</div>
          </div>
          <div class="support-action__end"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg></div>
        </button>
        <div class="support-action-divider"></div>
        <button type="button" class="support-action" id="sp-chat-btn">
          <div class="support-action__ico" style="background:rgba(174,206,97,.18);color:var(--lime-deep,#6f8f2f)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          </div>
          <div>
            <div class="support-action__title">Talk to an Agent</div>
            <div class="support-action__desc">Realtime RTM chat</div>
          </div>
          <div style="background:var(--lime-soft,#eef6d4);color:var(--navy,#0f2744);font-size:9px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;padding:3px 9px;border-radius:999px;white-space:nowrap">Online</div>
        </button>
        <div class="support-action-divider"></div>
        <a class="support-action" href="${supportUrl()}" id="sp-portal-link">
          <div class="support-action__ico" style="background:var(--slate-100,#f1f5f9);color:var(--slate-600,#475569)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/></svg>
          </div>
          <div>
            <div class="support-action__title">Support Portal</div>
            <div class="support-action__desc">Full Fluent Support customer portal</div>
          </div>
          <div class="support-action__end"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg></div>
        </a>
      </div>
      <div class="sp-form-wrap" id="sp-form-view">
        <div class="sp-form-head">
          <button type="button" class="sp-form-head__back" id="sp-back-btn" aria-label="Back">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M15 18l-6-6 6-6"/></svg>
          </button>
          <div class="sp-form-head__t">New Support Ticket</div>
        </div>
        <div id="sp-form-fields">
          <div class="sp-field">
            <label for="sp-name">Your name</label>
            <input type="text" id="sp-name" autocomplete="name" placeholder="Full name" />
          </div>
          <div class="sp-field">
            <label for="sp-email">Email</label>
            <input type="email" id="sp-email" autocomplete="email" placeholder="you@email.com" required />
          </div>
          <div class="sp-field">
            <label for="sp-subject">Subject</label>
            <input type="text" id="sp-subject" placeholder="What do you need help with?" />
          </div>
          <div class="sp-field">
            <label for="sp-category">Category</label>
            <select id="sp-category">
              <option>Booking &amp; Scheduling</option>
              <option>Payment &amp; Billing</option>
              <option>Technical Issue</option>
              <option>Tutor Complaint</option>
              <option>Account &amp; Profile</option>
              <option>Other</option>
            </select>
          </div>
          <div class="sp-field">
            <label for="sp-message">Message</label>
            <textarea id="sp-message" placeholder="Describe your issue in detail…"></textarea>
          </div>
          <p id="sp-form-error" style="display:none;color:#b91c1c;font-size:12px;font-weight:700;margin:0 0 8px"></p>
          <button type="button" class="btn btn--primary btn--block" id="sp-submit" style="padding:12px 18px;font-size:12px;margin-top:4px">
            Submit Ticket
          </button>
        </div>
        <div class="sp-success" id="sp-success">
          <div class="sp-success__ico">✅</div>
          <div class="sp-success__t">Ticket Submitted!</div>
          <div class="sp-success__d">We'll respond within 2 business hours.<br/>Track it in the <a href="${supportUrl()}">Support Portal</a>.</div>
        </div>
      </div>
      <div class="support-panel__foot">
        <a class="support-panel__foot-link" href="${supportUrl()}" id="sp-foot-link">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          Support Centre
        </a>
        <div class="support-online">Agents Online</div>
      </div>`;
    document.body.appendChild(el);
  }

  function initBackToTop() {
    const btn = $("#back-to-top");
    if (!btn) return;
    btn.classList.add("is-visible");
    btn.addEventListener("click", () => {
      if (window.NGT_LENIS && typeof window.NGT_LENIS.scrollTo === "function") {
        window.NGT_LENIS.scrollTo(0, { duration: 1.1 });
      } else {
        window.scrollTo({ top: 0, behavior: "smooth" });
      }
    });
  }

  function initChatTrigger() {
    const btn = $("#chat-dock-btn");
    if (!btn) return;
    btn.addEventListener("click", () => {
      const panel = $("#chat-panel");
      if (panel) panel.classList.toggle("is-open");
      btn.classList.remove("has-unread");
      const sp = $("#support-panel");
      if (sp) sp.classList.remove("is-open");
      document.dispatchEvent(new CustomEvent("ngt:rtm-open"));
    });
  }

  async function submitFluentTicket() {
    const support = cfg().support || {};
    const errEl = $("#sp-form-error");
    const subjectEl = $("#sp-subject");
    const emailEl = $("#sp-email");
    const messageEl = $("#sp-message");
    const nameEl = $("#sp-name");
    const categoryEl = $("#sp-category");
    const formFields = $("#sp-form-fields");
    const successEl = $("#sp-success");

    if (errEl) { errEl.style.display = "none"; errEl.textContent = ""; }

    const subject = (subjectEl && subjectEl.value || "").trim();
    const email = (emailEl && emailEl.value || "").trim();
    const message = (messageEl && messageEl.value || "").trim();
    const name = (nameEl && nameEl.value || "").trim();

    if (!subject) { if (subjectEl) { subjectEl.focus(); subjectEl.style.borderColor = "#ef4444"; } return; }
    if (!email) { if (emailEl) { emailEl.focus(); emailEl.style.borderColor = "#ef4444"; } return; }
    if (!message) { if (messageEl) { messageEl.focus(); messageEl.style.borderColor = "#ef4444"; } return; }

    const endpoint = support.ticketUrl || "/wp-json/ngc/v1/support/tickets";
    const submitBtn = $("#sp-submit");
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = "Submitting…"; }

    try {
      const res = await fetch(endpoint, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": support.nonce || ""
        },
        body: JSON.stringify({
          name: name,
          email: email,
          subject: subject,
          message: message,
          category: categoryEl ? categoryEl.value : ""
        })
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.ok === false) {
        throw new Error((data && (data.message || data.code)) || "Ticket could not be created");
      }
      if (formFields) formFields.style.display = "none";
      if (successEl) successEl.classList.add("show");
    } catch (e) {
      if (errEl) {
        errEl.style.display = "block";
        errEl.textContent = e.message || "Submission failed. Opening support portal…";
      }
      // Fallback: open portal if API unavailable
      if (!support.active) {
        window.location.href = supportUrl();
      }
    } finally {
      if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = "Submit Ticket"; }
    }
  }

  function initSupportPanel() {
    const dockBtn = $("#support-dock-btn");
    const panel = $("#support-panel");
    const closeBtn = $("#support-close");
    const ticketBtn = $("#sp-ticket-btn");
    const chatBtn = $("#sp-chat-btn");
    const backBtn = $("#sp-back-btn");
    const submitBtn = $("#sp-submit");
    const actionsView = $("#sp-actions-view");
    const formView = $("#sp-form-view");
    const formFields = $("#sp-form-fields");
    const successEl = $("#sp-success");
    const foot = panel ? panel.querySelector(".support-panel__foot") : null;

    if (!dockBtn || !panel) return;

    // Keep portal links fresh from localized config.
    const url = supportUrl();
    ["#sp-portal-link", "#sp-foot-link"].forEach((sel) => {
      const a = $(sel);
      if (a) a.setAttribute("href", url);
    });

    dockBtn.addEventListener("click", () => {
      const isOpen = panel.classList.contains("is-open");
      panel.classList.toggle("is-open", !isOpen);
      const chatPanel = $("#chat-panel");
      if (chatPanel && !isOpen) chatPanel.classList.remove("is-open");
    });

    closeBtn && closeBtn.addEventListener("click", () => panel.classList.remove("is-open"));

    document.addEventListener("click", (e) => {
      if (panel.classList.contains("is-open") &&
          !e.target.closest("#support-panel") &&
          !e.target.closest("#support-dock-btn")) {
        panel.classList.remove("is-open");
      }
    });

    ticketBtn && ticketBtn.addEventListener("click", () => {
      actionsView.style.display = "none";
      formView.classList.add("is-open");
      if (foot) foot.style.display = "none";
    });

    backBtn && backBtn.addEventListener("click", () => {
      actionsView.style.display = "";
      formView.classList.remove("is-open");
      if (foot) foot.style.display = "";
      if (formFields) formFields.style.display = "";
      if (successEl) successEl.classList.remove("show");
    });

    chatBtn && chatBtn.addEventListener("click", () => {
      panel.classList.remove("is-open");
      const chatPanel = $("#chat-panel");
      if (chatPanel) chatPanel.classList.add("is-open");
      const chatDockBtn = $("#chat-dock-btn");
      if (chatDockBtn) chatDockBtn.classList.remove("has-unread");
      document.dispatchEvent(new CustomEvent("ngt:rtm-open"));
    });

    submitBtn && submitBtn.addEventListener("click", submitFluentTicket);
  }

  function boot() {
    injectCss();
    buildDock();
    buildSupportPanel();
    document.body.classList.add("has-float-dock");
    const ready = () => {
      initBackToTop();
      initChatTrigger();
      initSupportPanel();
      const wa = $(".fdock-btn--wa");
      if (wa) wa.href = waHref();
    };
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", ready);
    else ready();
  }
  boot();
})();
