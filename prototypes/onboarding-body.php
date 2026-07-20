<?php
/** Auto-extracted from onboarding.html — do not edit DOM structure. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>


<section class="admin-bar">
  <div class="pagehead__bg" aria-hidden="true"><div class="pagehead__mesh"></div><div class="pagehead__grid"></div></div>
  <div class="wrap admin-bar__inner">
    <div>
      <span class="eyebrow" style="color:var(--lime);display:block;margin-bottom:8px">System Admin</span>
      <div class="admin-bar__title">Onboarding Management</div>
      <div class="admin-bar__sub">Configure steps · Track progress · Gamify · Notify</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
      <a class="btn btn--lime btn--shine" href="#configure">Configure Steps</a>
      <a class="btn btn--ghost" href="<?php echo esc_url( ngt_get_page_url( 'admin-dashboard' ) ); ?>" data-internal>← Admin Dashboard</a>
    </div>
  </div>
</section>

<div class="wrap" style="padding-bottom:80px">
  <!-- KPI Overview -->
  <div class="admin-super-grid" style="margin-top:-28px;position:relative;z-index:10;margin-bottom:32px">
    <div class="admin-kpi"><div class="admin-kpi__val"><span class="counter" data-target="73">0</span></div><div class="admin-kpi__lbl">Total Enrolled</div><div class="admin-kpi__trend"><i data-lucide="users" style="width:13px;height:13px"></i> Tutors + Staff + Support</div></div>
    <div class="admin-kpi"><div class="admin-kpi__val"><span class="counter" data-target="68">0</span>%</div><div class="admin-kpi__lbl">Avg Completion</div><div class="admin-kpi__trend"><i data-lucide="trending-up" style="width:13px;height:13px"></i> +6% this week</div></div>
    <div class="admin-kpi"><div class="admin-kpi__val" style="color:#ef4444"><span class="counter" data-target="9">0</span></div><div class="admin-kpi__lbl">Overdue (&gt;7 days)</div><div class="admin-kpi__trend down"><i data-lucide="alert-triangle" style="width:13px;height:13px"></i> Alerts sent</div></div>
    <div class="admin-kpi"><div class="admin-kpi__val" style="color:#16a34a"><span class="counter" data-target="41">0</span></div><div class="admin-kpi__lbl">Fully Certified</div><div class="admin-kpi__trend"><i data-lucide="award" style="width:13px;height:13px"></i> 100% complete</div></div>
  </div>

  <!-- Progress Table -->
  <div class="panel" style="margin-bottom:24px">
    <div class="panel__h">
      <h2>Team Progress</h2>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <div class="onb-filters" id="onb-filters">
          <button class="onb-filter is-active" data-dept="all">All</button>
          <button class="onb-filter" data-dept="tutor">Tutors</button>
          <button class="onb-filter" data-dept="staff">Staff</button>
          <button class="onb-filter" data-dept="support">Support</button>
        </div>
      </div>
    </div>
    <div style="overflow-x:auto">
      <table class="prog-table dash-table" id="onb-table">
        <thead><tr><th>Person</th><th>Department</th><th>Progress</th><th>Steps Done</th><th>Points</th><th>Last Activity</th><th>Notifications</th><th>Actions</th></tr></thead>
        <tbody id="onb-tbody"></tbody>
      </table>
    </div>
  </div>

  <div class="admin-layout" style="margin-bottom:24px">
    <!-- Configure Steps -->
    <div class="panel" id="configure">
      <div class="panel__h"><h2>Configure Onboarding Steps</h2><span class="chip" style="background:var(--lime-soft);border-color:rgba(174,206,97,.4);color:var(--navy)">Admin Only</span></div>
      <p style="font-size:13px;color:var(--slate-500);font-weight:500;margin-bottom:18px">Drag to reorder. Toggle to enable/disable. Points awarded on completion.</p>
      <div id="steps-config">
        <div class="step-config"><span class="step-config__drag"><i data-lucide="grip-vertical"></i></span><div class="flex-1"><div class="step-config__t">Profile Setup &amp; Photo Upload</div><div class="step-config__pts">50 pts</div></div><div class="step-toggle" title="Toggle step"></div></div>
        <div class="step-config"><span class="step-config__drag"><i data-lucide="grip-vertical"></i></span><div class="flex-1"><div class="step-config__t">Upload SA ID Document</div><div class="step-config__pts">100 pts · Required</div></div><div class="step-toggle" title="Toggle step"></div></div>
        <div class="step-config"><span class="step-config__drag"><i data-lucide="grip-vertical"></i></span><div class="flex-1"><div class="step-config__t">Upload Qualifications / SACE</div><div class="step-config__pts">100 pts · Required</div></div><div class="step-toggle" title="Toggle step"></div></div>
        <div class="step-config"><span class="step-config__drag"><i data-lucide="grip-vertical"></i></span><div class="flex-1"><div class="step-config__t">Police Clearance Certificate</div><div class="step-config__pts">150 pts · Required</div></div><div class="step-toggle" title="Toggle step"></div></div>
        <div class="step-config"><span class="step-config__drag"><i data-lucide="grip-vertical"></i></span><div class="flex-1"><div class="step-config__t">Subject Competency Assessment</div><div class="step-config__pts">200 pts</div></div><div class="step-toggle" title="Toggle step"></div></div>
        <div class="step-config"><span class="step-config__drag"><i data-lucide="grip-vertical"></i></span><div class="flex-1"><div class="step-config__t">Teaching Trial Session</div><div class="step-config__pts">250 pts</div></div><div class="step-toggle" title="Toggle step"></div></div>
        <div class="step-config"><span class="step-config__drag"><i data-lucide="grip-vertical"></i></span><div class="flex-1"><div class="step-config__t">MasterStudy Orientation Course</div><div class="step-config__pts">75 pts</div></div><div class="step-toggle off" title="Toggle step"></div></div>
      </div>
      <button class="btn btn--primary btn--block btn--shine" style="margin-top:18px" onclick="this.textContent='✓ Saved';this.style.background='#15803d';setTimeout(()=>{this.textContent='Save Configuration';this.style.background='';},2000)">Save Configuration</button>
    </div>

    <!-- Analytics + Gamification -->
    <div class="dash-col">
      <div class="panel">
        <div class="panel__h"><h2>Completion by Department</h2></div>
        <div style="display:flex;flex-direction:column;gap:14px" id="dept-chart"></div>
      </div>
      <div class="panel">
        <div class="panel__h"><h2>Top Performers</h2><span class="chip" style="background:var(--lime-soft);border-color:rgba(174,206,97,.4);color:var(--navy)">Gamified</span></div>
        <div id="top-performers"></div>
      </div>
    </div>
  </div>

  <!-- Notification History -->
  <div class="panel">
    <div class="panel__h"><h2>Notification History</h2><span style="font-size:11px;font-weight:800;text-transform:uppercase;color:var(--slate-400)">Full Audit Log</span></div>
    <p style="font-size:13px;color:var(--slate-500);font-weight:500;margin-bottom:16px">All onboarding notifications — in-app (default), email and SMS. Real-time delivery status.</p>
    <div class="notif-history" id="notif-history"></div>
  </div>
</div><script src="https://cdn.jsdelivr.net/npm/lenis@1.1.14/dist/lenis.min.js"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>