<?php
/**
 * Color-scheme (dark mode) support.
 *
 * The unified token layer defines `html[data-bi-scheme="dark"]` overrides, so
 * dark mode is purely an attribute swap. The user preference is stored in
 * localStorage and applied before first paint to avoid a flash.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Apply the stored scheme preference before first paint.
 */
add_action( 'wp_head', 'bi_scheme_head_script', 1 );
function bi_scheme_head_script() {
    ?>
    <script>
    (function () {
      try {
        var html = document.documentElement;
        window.__biBaseScheme = html.getAttribute('data-bi-scheme') || 'default';
        var pref = localStorage.getItem('bi-scheme');
        if (pref === 'dark') { html.setAttribute('data-bi-scheme', 'dark'); }
      } catch (e) {}
    })();
    </script>
    <?php
}

/**
 * Enqueue the scheme toggle behaviour.
 */
add_action( 'wp_enqueue_scripts', 'bi_scheme_enqueue', 20 );
function bi_scheme_enqueue() {
    if ( is_admin() ) {
        return;
    }
    wp_enqueue_script( 'bi-scheme', BI_URI . '/assets/js/bi-scheme.js', [], BI_VERSION, true );
}

/**
 * Render the dark-mode toggle button (used by header templates).
 */
function bi_scheme_toggle_button() {
    ?>
    <button type="button" class="bi-scheme-toggle" data-bi-scheme-toggle
        aria-label="<?php esc_attr_e( 'Toggle dark mode', 'beyondinfinity' ); ?>" aria-pressed="false">
      <svg class="bi-scheme-toggle__moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
      </svg>
      <svg class="bi-scheme-toggle__sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="5"/>
        <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
        <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
      </svg>
    </button>
    <?php
}
