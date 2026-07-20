<?php
/**
 * Admin dashboard prototype — wired for admin-dash.js + reports.js.
 *
 * @package NextGen_Tutors
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$revenue  = 0;
$sessions = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}ngt_session_logs WHERE attendance = 'present'" ); // phpcs:ignore
$tutors   = (int) wp_count_posts( 'tutors' )->publish;
$pending  = (int) wp_count_posts( 'tutors' )->pending;
?>
<section class="dash-head">
  <div class="dash-head__bg" aria-hidden="true"><div class="pagehead__mesh"></div><div class="pagehead__grid"></div></div>
  <div class="wrap dash-head__inner">
    <div class="dash-hello">
      <span class="eyebrow dash-hello__eyebrow">Platform Admin</span>
      <h1>Operations Dashboard</h1>
      <p>Revenue, sessions, tutor vetting and platform health at a glance.</p>
    </div>
    <div class="dash-avatar">
      <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&q=80&w=200&h=200" alt="Admin" referrerpolicy="no-referrer" />
      <div>
        <div class="dash-avatar__n"><?php echo esc_html( wp_get_current_user()->display_name ); ?></div>
        <div class="dash-avatar__r">NextGen Tutors Admin</div>
      </div>
    </div>
  </div>
</section>

<div class="wrap" style="padding-bottom:80px">
  <div class="admin-kpi-grid">
    <div class="admin-kpi" data-reveal>
      <span class="admin-kpi__ico">💰</span>
      <div>
        <div class="admin-kpi__val">R<span class="counter" id="kpi-revenue-val" data-target="<?php echo esc_attr( (int) $revenue ); ?>">0</span></div>
        <div class="admin-kpi__lbl">Revenue (range)</div>
      </div>
    </div>
    <div class="admin-kpi" data-reveal>
      <span class="admin-kpi__ico">📅</span>
      <div>
        <div class="admin-kpi__val"><span class="counter" id="kpi-sessions-val" data-target="<?php echo esc_attr( $sessions ); ?>">0</span></div>
        <div class="admin-kpi__lbl">Sessions Completed</div>
      </div>
    </div>
    <div class="admin-kpi" data-reveal>
      <span class="admin-kpi__ico">👩‍🏫</span>
      <div>
        <div class="admin-kpi__val"><span class="counter" data-target="<?php echo esc_attr( $tutors ); ?>">0</span></div>
        <div class="admin-kpi__lbl">Active Tutors</div>
      </div>
    </div>
    <div class="admin-kpi" data-reveal>
      <span class="admin-kpi__ico">🛡️</span>
      <div>
        <div class="admin-kpi__val"><span class="counter" data-target="<?php echo esc_attr( $pending ); ?>">0</span></div>
        <div class="admin-kpi__lbl">Pending Applications</div>
      </div>
    </div>
  </div>

  <div class="panel admin-range" data-reveal>
    <div class="panel__h"><h2>Date Range</h2></div>
    <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
      <div class="field"><label for="date-from">From</label><input type="date" id="date-from" value="<?php echo esc_attr( gmdate( 'Y-m-01' ) ); ?>" /></div>
      <div class="field"><label for="date-to">To</label><input type="date" id="date-to" value="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" /></div>
      <button class="btn btn--lime" type="button" id="apply-range">Apply Range</button>
    </div>
  </div>

  <div class="dash-grid">
    <div class="dash-col">
      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Revenue Trend</h2></div>
        <div class="rchart" id="revenue-chart"></div>
      </div>
      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Sessions by Subject</h2></div>
        <div class="rchart rchart--compact" id="subject-chart"></div>
      </div>
      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Engagement Funnel</h2></div>
        <div id="engagement-bars" style="display:flex;flex-direction:column;gap:14px"></div>
      </div>
    </div>
    <div class="dash-col">
      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Session Format</h2></div>
        <div id="format-donuts" style="display:flex;gap:20px;justify-content:center;flex-wrap:wrap"></div>
      </div>
      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Top Subjects</h2></div>
        <div id="top-subjects"></div>
      </div>
      <div class="panel" data-reveal>
        <div class="panel__h"><h2>Tutor Applications</h2><span class="chip"><?php echo esc_html( $pending ); ?> Applications</span></div>
        <?php
        $apps = get_posts(
			array(
				'post_type'      => 'tutors',
				'post_status'    => 'pending',
				'posts_per_page' => 5,
			)
		);
		if ( $apps ) {
			foreach ( $apps as $app ) {
				?>
        <div class="approval-card">
          <div>
            <div class="approval-card__name"><?php echo esc_html( $app->post_title ); ?></div>
            <div class="approval-card__meta"><?php echo esc_html( wp_trim_words( $app->post_content, 12 ) ); ?></div>
          </div>
          <div class="approval-card__actions">
            <button class="btn btn--lime btn-approve" type="button" data-id="<?php echo esc_attr( $app->ID ); ?>">Approve</button>
            <button class="btn btn--ghost btn-reject" type="button" data-id="<?php echo esc_attr( $app->ID ); ?>">Reject</button>
          </div>
        </div>
				<?php
			}
		} else {
			echo '<p style="color:var(--slate-500);font-weight:600">No pending applications.</p>';
		}
		?>
      </div>
    </div>
  </div>
</div>
