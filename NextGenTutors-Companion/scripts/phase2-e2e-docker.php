<?php
/**
 * Phase 2 Docker E2E — live integrations, CMS coverage, child learner provisioning.
 *
 * Usage:
 *   docker exec nextgentutors-wordpress-1 php /var/www/html/wp-cli.phar eval-file \
 *     /var/www/html/wp-content/plugins/NextGenTutors-Companion/scripts/phase2-e2e-docker.php --allow-root
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run inside WordPress.\n" );
	exit( 1 );
}

$errors = 0;
$checks = [];

/**
 * @param string $name   Check name.
 * @param bool   $ok     Result.
 * @param string $detail Detail.
 */
function ngc_p2_assert( $name, $ok, $detail = '' ) {
	global $checks, $errors;
	$checks[] = [ 'name' => $name, 'ok' => $ok, 'detail' => $detail ];
	if ( ! $ok ) {
		++$errors;
	}
}

// 1. Section CMS — all 11 blocks seeded with theme map
NGC_Section_CMS::install_defaults();
$keys = NGC_Section_CMS::section_keys();
ngc_p2_assert( 'cms_section_count', 11 === count( $keys ), (string) count( $keys ) );
ngc_p2_assert( 'cms_theme_map', 9 === count( NGC_Section_CMS::theme_section_option_map() ), '' );
foreach ( $keys as $key ) {
	$row = NGC_Section_CMS::get_section_row( 'home', $key );
	ngc_p2_assert( 'cms_row_' . $key, ! empty( $row['section_key'] ) && $row['section_key'] === $key, $row['section_key'] ?? 'missing' );
}

// 2. Child learner auto-provision
$parent = get_users( [ 'role' => 'parent', 'number' => 1, 'fields' => 'ID' ] );
if ( ! $parent ) {
	$parent_id = wp_create_user( 'p2parent', wp_generate_password(), 'p2parent@test.local' );
	if ( ! is_wp_error( $parent_id ) ) {
		$user = get_user_by( 'id', $parent_id );
		if ( $user ) {
			$user->set_role( 'parent' );
		}
	} else {
		$parent_id = 0;
	}
} else {
	$parent_id = (int) $parent[0];
}

if ( $parent_id ) {
	$child_id = NGC_Child_Learners::create(
		[
			'parent_user_id' => $parent_id,
			'display_name'   => 'Phase2 Learner',
			'grade'          => 'Grade 10',
		]
	);
	ngc_p2_assert( 'child_create', ! is_wp_error( $child_id ), is_wp_error( $child_id ) ? $child_id->get_error_message() : '' );
	if ( ! is_wp_error( $child_id ) ) {
		$row = NGC_Child_Learners::get( (int) $child_id );
		ngc_p2_assert( 'child_wp_user', ! empty( $row['student_user_id'] ), '' );
		$student = get_user_by( 'id', (int) ( $row['student_user_id'] ?? 0 ) );
		ngc_p2_assert( 'child_learner_role', $student && in_array( 'child_learner', (array) $student->roles, true ), '' );
	}
} else {
	ngc_p2_assert( 'child_create', false, 'no parent user' );
}

// 3. Live integration stack
$wc_active = class_exists( 'WooCommerce' );
$lms_active = defined( 'STM_LMS_VERSION' ) || class_exists( 'STM_LMS_Course' );
ngc_p2_assert( 'woocommerce_active', $wc_active, $wc_active ? 'active' : 'install via install-phase2-stack.ps1' );
ngc_p2_assert( 'masterstudy_active', $lms_active, $lms_active ? 'active' : 'install via install-phase2-stack.ps1' );

if ( class_exists( 'NGC_Masterstudy_Adapter' ) ) {
	$ms = new NGC_Masterstudy_Adapter();
	$verify = $ms->verify();
	ngc_p2_assert( 'masterstudy_verify', ! empty( $verify['ok'] ) || ! $lms_active, $verify['status'] ?? '' );
}

if ( class_exists( 'NGC_Amelia_Adapter' ) ) {
	$amelia = new NGC_Amelia_Adapter();
	ngc_p2_assert( 'amelia_adapter', method_exists( $amelia, 'verify' ), '' );
}

// 4. Tutor integration provision dry-run
$tutor = get_users( [ 'role' => 'tutor', 'number' => 1, 'fields' => 'ID' ] );
if ( $tutor && class_exists( 'NGC_Tutor_Cpt_Source' ) ) {
	$dry = NGC_Tutor_Cpt_Source::provision_integrations_for_user( (int) $tutor[0], true );
	ngc_p2_assert( 'tutor_provision_dry', ! empty( $dry['ok'] ), '' );
	if ( $lms_active ) {
		$live = NGC_Tutor_Cpt_Source::provision_integrations_for_user( (int) $tutor[0], false );
		ngc_p2_assert( 'tutor_masterstudy_live', ! empty( $live['masterstudy']['ok'] ) || ! empty( $live['masterstudy']['id'] ), wp_json_encode( $live['masterstudy'] ?? [] ) );
	}
} else {
	ngc_p2_assert( 'tutor_provision_dry', true, 'skipped — no tutor user' );
}

// 5. PayFast export path (companion code — gateway optional)
ngc_p2_assert( 'payout_export_ready', class_exists( 'NGC_Payout_Export' ), '' );

echo "NextGen Companion — Phase 2 Docker E2E\n";
echo str_repeat( '-', 40 ) . "\n";
foreach ( $checks as $c ) {
	echo ( $c['ok'] ? 'OK  ' : 'FAIL' ) . ' ' . $c['name'];
	if ( $c['detail'] ) {
		echo ' — ' . $c['detail'];
	}
	echo "\n";
}
echo str_repeat( '-', 40 ) . "\n";
echo $errors ? "FAILED with {$errors} error(s)\n" : "OK — Phase 2 checks passed\n";
exit( $errors ? 1 : 0 );
