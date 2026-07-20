<?php
/**
 * DEPRECATED — do not load on production sites.
 *
 * Legacy standalone orchestrator (ngt_* tables). Functionality is implemented in
 * NextGenTutors-Companion v1.4+ via NGC_Integrate_Runtime and includes/integrations/*.
 *
 * @deprecated 1.4.0 Use NextGenTutors-Companion plugin bootstrap instead.
 * @see integrate/README.md
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'NGC_LOAD_LEGACY_ORCHESTRATOR' ) ) {
	trigger_error(
		'nextgentutors-workflows.php is deprecated. Activate NextGenTutors-Companion v1.4+ instead.',
		E_USER_DEPRECATED
	);
	return;
}

/**
 * Legacy stub — retained only when NGC_LOAD_LEGACY_ORCHESTRATOR is explicitly defined.
 */
class NextGen_Complete_Orchestrator {
	/**
	 * @deprecated 1.4.0
	 */
	public function __construct() {
		add_action( 'admin_notices', [ $this, 'deprecation_notice' ] );
	}

	/**
	 * Admin notice for sites that still load this file.
	 */
	public function deprecation_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>';
		echo esc_html__(
			'NextGen legacy orchestrator is deprecated. Use NextGenTutors-Companion v1.4+ integrate runtime.',
			'nextgencompanion'
		);
		echo '</p></div>';
	}
}
