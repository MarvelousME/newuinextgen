<?php
/** Auto-extracted from tutor-dashboard.html — do not edit DOM structure. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>


<section class="tdb-head">
  <div class="pagehead__bg" aria-hidden="true"><div class="pagehead__mesh"></div><div class="pagehead__grid"></div></div>
  <div class="wrap tdb-head__inner">
    <div>
      <span class="eyebrow" style="color:var(--lime);display:block;margin-bottom:10px">Tutor Portal</span>
      <h1 style="font-family:var(--font-serif);font-weight:900;text-transform:uppercase;font-size:clamp(28px,4vw,44px);color:#fff;margin-bottom:8px">Welcome, Karabo</h1>
      <p style="color:rgba(255,255,255,.65);font-size:14px;font-weight:500">Track your earnings, sessions and account standing.</p>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px">
        <span class="tdb-status tdb-status--active"><i data-lucide="badge-check"></i> Fully Verified</span>
        <span class="tdb-status tdb-status--active"><i data-lucide="star"></i> Top Rated</span>
        <span class="tdb-status tdb-status--pending"><i data-lucide="clock"></i> Payout Pending</span>
      </div>
    </div>
    <div class="dash-avatar">
      <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=200&h=200" alt="Karabo Molefe" referrerpolicy="no-referrer" />
      <div>
        <div class="dash-avatar__n">Karabo Molefe</div>
        <div class="dash-avatar__r">UCT · Maths &amp; Physics</div>
      </div>
    </div>
  </div>
</section>

<div class="wrap" style="padding-bottom:80px">
  <div class="admin-super-grid" style="margin-top:-32px;position:relative;z-index:10;margin-bottom:32px">
    <div class="admin-kpi"><div class="admin-kpi__val">R<span class="counter" data-target="9350">0</span></div><div class="admin-kpi__lbl">Monthly Earnings (net)</div><div class="admin-kpi__trend"><i data-lucide="trending-up" style="width:13px;height:13px"></i> +12% vs last month</div></div>
    <div class="admin-kpi"><div class="admin-kpi__val"><span class="counter" data-target="28">0</span></div><div class="admin-kpi__lbl">Sessions This Month</div><div class="admin-kpi__trend"><i data-lucide="trending-up" style="width:13px;height:13px"></i> +4 sessions</div></div>
    <div class="admin-kpi"><div class="admin-kpi__val">4.95</div><div class="admin-kpi__lbl">Average Rating</div><div class="admin-kpi__trend" style="color:var(--amber)"><i data-lucide="star" style="width:13px;height:13px;fill:var(--amber);stroke:var(--amber)"></i> Top 5% of tutors</div></div>
    <div class="admin-kpi"><div class="admin-kpi__val" style="font-size:28px;color:#16a34a">R9,350</div><div class="admin-kpi__lbl">Payout Due (1 Jul)</div><div class="admin-kpi__trend"><i data-lucide="calendar" style="width:13px;height:13px"></i> EFT on 1st of month</div></div>
  </div>

  <div class="dash-grid">
    <!-- LEFT -->
    <div class="dash-col">
      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Monthly Earnings</h2><span class="chip" style="background:var(--slate-100);border-color:var(--slate-200);color:var(--slate-500)">After 15% Platform Fee</span></div>
        <div class="earn-chart" id="earn-chart"></div>
      </div>

      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Upcoming Sessions</h2><a class="inv-link" href="<?php echo esc_url( ngt_get_page_url( 'tutor-profile' ) ); ?>" data-internal>Set availability <i data-lucide="arrow-right"></i></a></div>
        <div class="session-row">
          <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&q=80&w=120&h=120" alt="Naledi" referrerpolicy="no-referrer" />
          <div><div class="session-row__t">Naledi Maduna · Mathematics</div><div class="session-row__m">Thu, 11 Jun 2026 · 15:00 · 60 min · Online</div></div>
          <span class="session-status session-status--done">Confirmed</span>
        </div>
        <div class="session-row">
          <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=120&h=120" alt="Sipho" referrerpolicy="no-referrer" />
          <div><div class="session-row__t">Sipho Khumalo · Physical Sciences</div><div class="session-row__m">Fri, 12 Jun 2026 · 10:00 · 90 min · In-Person</div></div>
          <span class="session-status session-status--done">Confirmed</span>
        </div>
        <div class="session-row">
          <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&q=80&w=120&h=120" alt="Priya" referrerpolicy="no-referrer" />
          <div><div class="session-row__t">Priya Reddy · Tertiary Stats</div><div class="session-row__m">Mon, 15 Jun 2026 · 14:00 · 60 min · Online</div></div>
          <span class="session-status session-status--rated">Awaiting Confirmation</span>
        </div>
      </div>

      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Payout History</h2><span class="chip" style="background:var(--slate-100);border-color:var(--slate-200);color:var(--slate-500)">EFT · Monthly</span></div>
        <div class="payout-row"><div class="payout-row__period">May 2026</div><div class="payout-row__amount">R8,415</div><span class="pay-pill pay-pill--paid">Paid</span><span style="font-size:12px;font-weight:700;color:var(--slate-400)">Ref: PAY-2026-05</span></div>
        <div class="payout-row"><div class="payout-row__period">Apr 2026</div><div class="payout-row__amount">R7,650</div><span class="pay-pill pay-pill--paid">Paid</span><span style="font-size:12px;font-weight:700;color:var(--slate-400)">Ref: PAY-2026-04</span></div>
        <div class="payout-row"><div class="payout-row__period">Mar 2026</div><div class="payout-row__amount">R9,010</div><span class="pay-pill pay-pill--paid">Paid</span><span style="font-size:12px;font-weight:700;color:var(--slate-400)">Ref: PAY-2026-03</span></div>
        <div class="payout-row"><div class="payout-row__period">Jun 2026</div><div class="payout-row__amount">R9,350</div><span class="pay-pill pay-pill--pending">Pending · 1 Jul</span><span style="font-size:12px;font-weight:700;color:var(--slate-400)">Auto EFT</span></div>
      </div>
    </div>

    <!-- RIGHT -->
    <div class="dash-col">
      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Vetting &amp; Onboarding</h2><span class="tdb-status tdb-status--active">Complete</span></div>
        <div class="vtrack">
          <div class="vtrack-step is-done"><span class="vtrack-ico"><i data-lucide="user-check"></i></span><div><div class="vtrack-body"><div class="vtrack-body__t">Application Reviewed</div><div class="vtrack-body__d">Approved 12 Jan 2026</div></div></div></div>
          <div class="vtrack-step is-done"><span class="vtrack-ico"><i data-lucide="file-text"></i></span><div><div class="vtrack-body"><div class="vtrack-body__t">Documents Verified</div><div class="vtrack-body__d">SA ID · Degree · SACE confirmed</div></div></div></div>
          <div class="vtrack-step is-done"><span class="vtrack-ico"><i data-lucide="flask-conical"></i></span><div><div class="vtrack-body"><div class="vtrack-body__t">Subject Competency</div><div class="vtrack-body__d">Scored 96% — Maths &amp; Physics</div></div></div></div>
          <div class="vtrack-step is-done"><span class="vtrack-ico"><i data-lucide="presentation"></i></span><div><div class="vtrack-body"><div class="vtrack-body__t">Teaching Trial</div><div class="vtrack-body__d">Mock session passed with strong comm. rating</div></div></div></div>
          <div class="vtrack-step is-done"><span class="vtrack-ico"><i data-lucide="fingerprint"></i></span><div><div class="vtrack-body"><div class="vtrack-body__t">Background Check</div><div class="vtrack-body__d">Police clearance cleared — SAPS / MIE</div></div></div></div>
        </div>
      </div>

      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Achievements</h2></div>
        <div class="badge-grid">
          <div class="badge is-earned"><span class="badge__e">🎓</span><div class="badge__n">Verified Tutor</div></div>
          <div class="badge is-earned"><span class="badge__e">⭐</span><div class="badge__n">Top Rated</div></div>
          <div class="badge is-earned"><span class="badge__e">🔥</span><div class="badge__n">Streak ×20</div></div>
          <div class="badge is-locked"><span class="badge__e">👑</span><div class="badge__n">50 Reviews</div></div>
          <div class="badge is-locked"><span class="badge__e">💰</span><div class="badge__n">R100k Earned</div></div>
          <div class="badge is-locked"><span class="badge__e">🌟</span><div class="badge__n">Popular Tutor</div></div>
        </div>
      </div>

      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Quick Actions</h2></div>
        <div style="display:flex;flex-direction:column;gap:10px">
          <a class="btn btn--primary btn--block" href="<?php echo esc_url( ngt_get_page_url( 'tutor-profile' ) ); ?>" data-internal>View My Public Profile</a>
          <a class="btn btn--ghost btn--block" href="<?php echo esc_url( ngt_get_page_url( 'contact' ) ); ?>" data-internal style="background:var(--slate-100);color:var(--navy);border-color:var(--slate-200)">Add Session Notes</a>
          <a class="btn btn--ghost btn--block" href="<?php echo esc_url( ngt_get_page_url( 'contact' ) ); ?>" data-internal style="background:var(--slate-100);color:var(--navy);border-color:var(--slate-200)">Request Availability Change</a>
        </div>
      </div>
    </div>
  </div>
</div><script src="https://cdn.jsdelivr.net/npm/lenis@1.1.14/dist/lenis.min.js"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>