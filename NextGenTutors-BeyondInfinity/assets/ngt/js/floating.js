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

  /**
   * Dock markup is owned by PHP bi_float_dock(). Do not inject a parallel HTML tree.
   * @return {Element|null}
   */
  function ensureDock() {
    const dock = $("#float-dock");
    if (!dock) {
      if (typeof console !== "undefined" && console.warn) {
        console.warn("[NGT] #float-dock missing — theme bi_float_dock() did not render.");
      }
      return null;
    }
    document.body.classList.add("has-float-dock");
    return dock;
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

  /**
   * Sole owner of #back-to-top — scroll reveal + single click handler.
   * Do not re-init from ngt-wp-bridge.js.
   */
  function initBackToTop() {
    const btn = $("#back-to-top");
    if (!btn || btn.getAttribute("data-bi-btt-ready") === "1") return;
    btn.setAttribute("data-bi-btt-ready", "1");

    const sync = () => {
      btn.classList.toggle("is-visible", window.scrollY > 500);
    };
    window.addEventListener("scroll", sync, { passive: true });
    sync();

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
          !e.target.closest("#support-dock-btn") &&
          !e.target.closest("#fab-toggle")) {
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
    ensureDock();
    buildSupportPanel();
    const ready = () => {
      if (!$("#float-dock")) return;
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
