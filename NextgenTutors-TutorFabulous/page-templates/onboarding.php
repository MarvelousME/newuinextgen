<?php
/**
 * Template Name: Onboarding
 */
defined('ABSPATH') || exit;

// Gate: must be logged in
if ( ! is_user_logged_in() ) {
    wp_safe_redirect( wp_login_url( get_permalink() ) );
    exit;
}

$user     = wp_get_current_user();
$role     = in_array('tutor', (array) $user->roles, true) ? 'tutor' : (in_array('parent_guardian', (array) $user->roles, true) ? 'parent' : 'student');
$name     = $user->display_name ?: $user->user_login;

// Fetch completion state from user meta
$completed_steps = (array) get_user_meta( $user->ID, 'ngt_onboarding_steps', true );

// Define steps per role
$steps = [];
if ( $role === 'tutor' ) {
    $steps = [
        ['complete_profile',   'Complete Your Profile',      'Add your bio, subjects, and teaching format.',                                     'user'],
        ['upload_docs',        'Upload Documents',           'ID, qualifications, and profile photo for vetting.',                               'upload'],
        ['set_availability',   'Set Availability',          'Connect your Amelia calendar and set your weekly schedule.',                        'calendar'],
        ['bank_details',       'Add Bank Details',          'Where we\'ll send your monthly payouts.',                                           'banknote'],
        ['first_session',      'Accept Your First Booking', 'Once approved, accept and complete your first session to unlock your Tutor badge.', 'star'],
    ];
} elseif ( $role === 'parent' ) {
    $steps = [
        ['complete_profile',   'Complete Your Profile',   'Add your name and contact details.',             'user'],
        ['add_child',          'Add Your Child',          'Create a student profile for each child you\'re managing.', 'user-plus'],
        ['find_tutor',         'Find a Tutor',            'Search for a tutor matching your child\'s grade and subject needs.', 'search'],
        ['first_booking',      'Make Your First Booking', 'Book and confirm your child\'s first session.',  'calendar-check'],
    ];
} else {
    // student
    $steps = [
        ['complete_profile',   'Complete Your Profile',   'Add your grade, subjects you need help with, and a profile photo.',  'user'],
        ['find_tutor',         'Find a Tutor',            'Browse verified tutors and pick one that matches your needs.',       'search'],
        ['first_booking',      'Book Your First Session', 'Book and pay for your first session via PayFast.',                  'calendar-check'],
        ['attend_session',     'Attend &amp; Rate',       'Attend your session and leave a rating. It helps the community.',   'star'],
    ];
}

$total_steps    = count($steps);
$done_steps     = count(array_intersect(array_column($steps, 0), $completed_steps));
$progress_pct   = $total_steps > 0 ? round(($done_steps / $total_steps) * 100) : 0;

get_template_part('template-parts/head');
get_template_part('template-parts/nav');
?>

<section class="onboarding-hero section" aria-labelledby="onboarding-hero-title">
  <div class="container onboarding-hero__inner">
    <span class="badge badge--lime">Welcome</span>
    <h1 id="onboarding-hero-title">Welcome, <?php echo esc_html(explode(' ', $name)[0]); ?>!</h1>
    <p>Let's get your <?php echo esc_html(ucfirst($role)); ?> account set up. You're <?php echo $progress_pct; ?>% of the way there.</p>
    <div class="onboarding-progress" role="progressbar" aria-valuenow="<?php echo $progress_pct; ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Onboarding progress">
      <div class="onboarding-progress__track">
        <div class="onboarding-progress__fill" style="width:<?php echo $progress_pct; ?>%"></div>
      </div>
      <span class="onboarding-progress__label"><?php echo $done_steps; ?> of <?php echo $total_steps; ?> steps complete</span>
    </div>
  </div>
</section>

<section class="onboarding-steps section" aria-labelledby="onboarding-steps-title">
  <div class="container">
    <h2 id="onboarding-steps-title" class="sr-only">Setup steps</h2>
    <ol class="onboarding-list" role="list">
      <?php foreach ($steps as $i => $step) :
        $key     = $step[0];
        $done    = in_array($key, $completed_steps, true);
        $current = !$done && ($i === 0 || in_array($steps[$i - 1][0], $completed_steps, true));
        $status  = $done ? 'done' : ($current ? 'current' : 'pending');
      ?>
        <li class="onboarding-item onboarding-item--<?php echo $status; ?>" data-step="<?php echo esc_attr($key); ?>"
            aria-current="<?php echo $current ? 'step' : 'false'; ?>">
          <div class="onboarding-item__icon" aria-hidden="true">
            <?php if ($done) : ?>
              <i data-lucide="check-circle"></i>
            <?php elseif ($current) : ?>
              <i data-lucide="<?php echo esc_attr($step[3]); ?>"></i>
            <?php else : ?>
              <i data-lucide="circle"></i>
            <?php endif; ?>
          </div>
          <div class="onboarding-item__body">
            <h3><?php echo $step[1]; // may contain &amp; ?></h3>
            <p><?php echo esc_html($step[2]); ?></p>
          </div>
          <div class="onboarding-item__action">
            <?php if ($done) : ?>
              <span class="status-pill status-pill--done">Done</span>
            <?php elseif ($current) : ?>
              <button class="btn btn--lime btn--sm onboarding-cta"
                      data-step="<?php echo esc_attr($key); ?>"
                      data-role="<?php echo esc_attr($role); ?>">
                Start
              </button>
            <?php else : ?>
              <span class="status-pill status-pill--pending">Pending</span>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<!-- STEP PANELS (shown as modal-like overlays when CTA clicked) -->
<div class="onboarding-panels" id="onboarding-panels" aria-live="polite">
  <?php
  $panel_actions = [
    'complete_profile' => [
      'title'   => 'Complete Your Profile',
      'content' => '<p>Update your profile details including your bio, subjects, and a professional photo.</p><a href="' . esc_url(admin_url('profile.php')) . '" class="btn btn--lime">Go to Profile Settings</a>',
    ],
    'upload_docs' => [
      'title'   => 'Upload Your Documents',
      'content' => '<p>Upload your ID and qualification documents to begin vetting.</p><a href="' . esc_url(ngt_get_page_url('tutor_dashboard')) . '?tab=documents" class="btn btn--lime">Go to Documents</a>',
    ],
    'set_availability' => [
      'title'   => 'Set Your Availability',
      'content' => '<p>Set your weekly teaching schedule so students can book you.</p><a href="' . esc_url(ngt_get_page_url('tutor_dashboard')) . '?tab=availability" class="btn btn--lime">Set Availability</a>',
    ],
    'bank_details' => [
      'title'   => 'Add Bank Details',
      'content' => '<p>We need your South African bank account details to process your monthly payouts.</p><a href="' . esc_url(ngt_get_page_url('tutor_dashboard')) . '?tab=payouts" class="btn btn--lime">Add Bank Details</a>',
    ],
    'find_tutor' => [
      'title'   => 'Find a Tutor',
      'content' => '<p>Browse our verified tutor directory filtered by subject, grade, and province.</p><a href="' . esc_url(ngt_get_page_url('find_tutor')) . '" class="btn btn--lime">Browse Tutors</a>',
    ],
    'first_booking' => [
      'title'   => 'Make Your First Booking',
      'content' => '<p>Find a tutor and book your first session. Payment is processed securely via PayFast.</p><a href="' . esc_url(ngt_get_page_url('find_tutor')) . '" class="btn btn--lime">Find a Tutor</a>',
    ],
    'add_child' => [
      'title'   => 'Add a Child Profile',
      'content' => '<p>Create a student profile for each child you are managing on the platform.</p><a href="' . esc_url(ngt_get_page_url('dashboard')) . '?tab=children" class="btn btn--lime">Manage Children</a>',
    ],
    'first_session' => [
      'title'   => 'Accept Your First Booking',
      'content' => '<p>Once your profile is approved, students will be able to book you. Accept and complete your first session to unlock your Tutor badge.</p><a href="' . esc_url(ngt_get_page_url('tutor_dashboard')) . '?tab=sessions" class="btn btn--lime">View Bookings</a>',
    ],
    'attend_session' => [
      'title'   => 'Attend &amp; Rate',
      'content' => '<p>Attend your session and then rate your experience to help other students find great tutors.</p><a href="' . esc_url(ngt_get_page_url('dashboard')) . '?tab=sessions" class="btn btn--lime">View Sessions</a>',
    ],
  ];
  foreach ($panel_actions as $key => $panel) : ?>
    <div class="onboarding-panel" id="panel-<?php echo esc_attr($key); ?>" role="dialog" aria-modal="true" aria-labelledby="panel-title-<?php echo esc_attr($key); ?>" hidden>
      <div class="onboarding-panel__inner">
        <button class="onboarding-panel__close" aria-label="Close"><i data-lucide="x"></i></button>
        <h3 id="panel-title-<?php echo esc_attr($key); ?>"><?php echo $panel['title']; ?></h3>
        <?php echo $panel['content']; ?>
        <button class="btn btn--outline btn--sm onboarding-mark-done" data-step="<?php echo esc_attr($key); ?>">
          Mark as Done
        </button>
      </div>
    </div>
  <?php endforeach; ?>
  <div class="onboarding-overlay" id="onboarding-overlay" hidden aria-hidden="true"></div>
</div>

<script>
(function () {
  const nonce = '<?php echo wp_create_nonce("ngt_onboarding_step"); ?>';
  const ajaxUrl = '<?php echo esc_js(admin_url("admin-ajax.php")); ?>';

  function openPanel(key) {
    const panel   = document.getElementById('panel-' + key);
    const overlay = document.getElementById('onboarding-overlay');
    if (!panel) return;
    document.querySelectorAll('.onboarding-panel').forEach(p => { p.hidden = true; });
    panel.hidden   = false;
    overlay.hidden = false;
    overlay.removeAttribute('aria-hidden');
    panel.querySelector('.onboarding-panel__close')?.focus();
  }

  function closeAll() {
    document.querySelectorAll('.onboarding-panel').forEach(p => { p.hidden = true; });
    const overlay = document.getElementById('onboarding-overlay');
    overlay.hidden = true;
    overlay.setAttribute('aria-hidden', 'true');
  }

  document.querySelectorAll('.onboarding-cta').forEach(btn => {
    btn.addEventListener('click', () => openPanel(btn.dataset.step));
  });

  document.querySelectorAll('.onboarding-panel__close').forEach(btn => {
    btn.addEventListener('click', closeAll);
  });

  document.getElementById('onboarding-overlay')?.addEventListener('click', closeAll);

  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAll(); });

  document.querySelectorAll('.onboarding-mark-done').forEach(btn => {
    btn.addEventListener('click', async function () {
      const step = this.dataset.step;
      const fd   = new FormData();
      fd.append('action', 'ngt_complete_onboarding_step');
      fd.append('step',   step);
      fd.append('nonce',  nonce);
      const res = await fetch(ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
      const data = await res.json();
      if (data.success) {
        closeAll();
        window.location.reload();
      }
    });
  });

  if (window.lucide) lucide.createIcons();
})();
</script>

<?php get_template_part('template-parts/footer'); ?>
<?php get_template_part('template-parts/footer-close'); ?>
