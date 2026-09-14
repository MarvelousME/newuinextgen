<?php
/** Auto-extracted from dashboard.html — do not edit DOM structure. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>


<section class="dash-head">
  <div class="dash-head__bg" aria-hidden="true"><div class="pagehead__mesh"></div><div class="pagehead__grid"></div></div>
  <div class="wrap dash-head__inner">
    <div class="dash-hello">
      <span class="eyebrow dash-hello__eyebrow">Your Learning Dashboard</span>
      <h1>Welcome back, Naledi</h1>
      <p>Track your progress and manage your tutoring sessions.</p>
    </div>
    <div class="dash-avatar">
      <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=200&h=200" alt="Naledi" referrerpolicy="no-referrer" />
      <div>
        <div class="dash-avatar__n">Naledi Maduna</div>
        <div class="dash-avatar__r">Grade 12 · IEB Learner</div>
      </div>
    </div>
  </div>
</section>

<div class="wrap" style="padding-bottom:80px">
  <div class="kpi-grid">
    <div class="kpi-card"><span class="kpi-ico" style="background:var(--lime-soft)">📚</span><div><div class="kpi-val"><span class="counter" data-target="18">0</span></div><div class="kpi-lbl">Sessions Completed</div></div></div>
    <div class="kpi-card"><span class="kpi-ico" style="background:#fef3c7">⭐</span><div><div class="kpi-val">4.9</div><div class="kpi-lbl">Avg Tutor Rating</div></div></div>
    <div class="kpi-card"><span class="kpi-ico" style="background:#dbeafe">💰</span><div><div class="kpi-val">R540</div><div class="kpi-lbl">Account Balance</div></div></div>
    <div class="kpi-card"><span class="kpi-ico" style="background:#fee2e2">🏆</span><div><div class="kpi-val"><span class="counter" data-target="3">0</span></div><div class="kpi-lbl">Achievements</div></div></div>
  </div>

  <div class="dash-grid">
    <!-- LEFT -->
    <div class="dash-col">
      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Next Session</h2><span class="chip">In 2 Days</span></div>
        <div class="next-session">
          <div class="next-session__glow"></div>
          <div>
            <div class="ns-tutor">
              <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=200&h=200" alt="Karabo" referrerpolicy="no-referrer" />
              <div>
                <div class="ns-name">Karabo Molefe</div>
                <div class="ns-meta">
                  <span><i data-lucide="book-open"></i> Mathematics</span>
                  <span><i data-lucide="calendar"></i> Thu, 11 Jun · 15:00</span>
                  <span><i data-lucide="clock"></i> 60 min</span>
                </div>
              </div>
            </div>
            <div class="ns-reminders"><i data-lucide="bell-ring"></i> Reminders set · 24h · 1h · 15m</div>
          </div>
          <a class="btn btn--lime btn--shine" href="#" style="position:relative;z-index:1">Join Class</a>
        </div>
      </div>

      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Recent Sessions</h2><a class="inv-link" href="#">View all <i data-lucide="arrow-right"></i></a></div>
        <div class="session-row">
          <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=120&h=120" alt="Lindiwe" referrerpolicy="no-referrer" />
          <div><div class="session-row__t">Lindiwe Nkosi · Physical Sciences</div><div class="session-row__m">Mon, 8 Jun · 60 min</div></div>
          <span class="session-status session-status--rated">★ Rated 5.0</span>
        </div>
        <div class="session-row">
          <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=120&h=120" alt="Karabo" referrerpolicy="no-referrer" />
          <div><div class="session-row__t">Karabo Molefe · Mathematics</div><div class="session-row__m">Fri, 5 Jun · 60 min</div></div>
          <span class="session-status session-status--rated">★ Rated 4.9</span>
        </div>
        <div class="session-row">
          <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=120&h=120" alt="Lindiwe" referrerpolicy="no-referrer" />
          <div><div class="session-row__t">Lindiwe Nkosi · Coding &amp; Python</div><div class="session-row__m">Wed, 3 Jun · 60 min</div></div>
          <span class="session-status session-status--done">Awaiting Review</span>
        </div>
      </div>

      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Billing &amp; Invoices</h2><span class="chip" style="background:var(--slate-100);border-color:var(--slate-200);color:var(--slate-500)">🔒 PayFast Secured</span></div>
        <div style="overflow-x:auto">
        <table class="dash-table">
          <thead><tr><th>Date</th><th>Description</th><th>Amount</th><th>Status</th><th>Invoice</th></tr></thead>
          <tbody>
            <tr><td>8 Jun 2026</td><td>Physical Sciences · 1 session</td><td class="amt">R320</td><td><span class="pay-pill pay-pill--paid">Paid</span></td><td><a class="inv-link" href="#"><i data-lucide="download"></i> PDF</a></td></tr>
            <tr><td>1 Jun 2026</td><td>10-Session Maths Bundle</td><td class="amt">R3,600</td><td><span class="pay-pill pay-pill--paid">Paid</span></td><td><a class="inv-link" href="#"><i data-lucide="download"></i> PDF</a></td></tr>
            <tr><td>11 Jun 2026</td><td>Mathematics · upcoming</td><td class="amt">R320</td><td><span class="pay-pill pay-pill--pending">Pending</span></td><td><span style="color:var(--slate-400)">—</span></td></tr>
          </tbody>
        </table>
        </div>
      </div>
    </div>

    <!-- RIGHT -->
    <div class="dash-col">
      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Achievements</h2></div>
        <div class="badge-grid">
          <div class="badge is-earned"><span class="badge__e">🥉</span><div class="badge__n">Beginner</div></div>
          <div class="badge is-earned"><span class="badge__e">🏃</span><div class="badge__n">Quick Learner</div></div>
          <div class="badge is-earned"><span class="badge__e">🎓</span><div class="badge__n">Scholar</div></div>
          <div class="badge is-locked"><span class="badge__e">🔥</span><div class="badge__n">Streak x10</div></div>
          <div class="badge is-locked"><span class="badge__e">👑</span><div class="badge__n">Master</div></div>
          <div class="badge is-locked"><span class="badge__e">🌟</span><div class="badge__n">Top 1%</div></div>
        </div>
      </div>

      <div class="panel" data-reveal style="padding:0;border:none;background:transparent">
        <div class="referral">
          <div class="referral__h">Refer a Friend &amp; Earn</div>
          <p class="referral__d">Share your link and earn <b style="color:var(--lime)">R50 credit</b> for every friend who books their first session.</p>
          <div class="referral__group">
            <input class="referral__input" id="referral-link" readonly value="nextgentutors.co.za/r/NALEDI50" />
            <button class="btn btn--lime" id="copy-ref" style="padding:11px 16px">Copy</button>
          </div>
          <p class="referral__stats">Referrals completed: <b id="ref-count">2</b> &nbsp;·&nbsp; Earned: <b>R<span id="ref-earn">100</span></b></p>
        </div>
      </div>

      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Quick Actions</h2></div>
        <div style="display:flex;flex-direction:column;gap:10px">
          <a class="btn btn--primary btn--block" href="<?php echo esc_url( ngt_get_page_url( 'find-a-tutor' ) ); ?>" data-internal>Book Another Session</a>
          <a class="btn btn--ghost btn--block" href="<?php echo esc_url( ngt_get_page_url( 'pricing' ) ); ?>" data-internal style="background:var(--slate-100);color:var(--navy);border-color:var(--slate-200)">View Bundles &amp; Pricing</a>
        </div>
      </div>
    </div>
  </div>
</div><script src="https://cdn.jsdelivr.net/npm/lenis@1.1.14/dist/lenis.min.js"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>