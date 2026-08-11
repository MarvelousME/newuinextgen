<?php
/**
 * Standalone unit checks for Beyond Measure (no WP bootstrap).
 */
declare(strict_types=1);

$root = dirname( __DIR__ );
require_once $root . '/src/Domain/Authorization/CapabilityCatalog.php';
require_once $root . '/src/Domain/Authorization/RoleCatalog.php';
require_once $root . '/src/Domain/Subsystem/SubsystemDefinition.php';
require_once $root . '/src/Domain/Subsystem/SubsystemRegistry.php';
require_once $root . '/src/Domain/Resource/ResourceCatalog.php';

$pass = 0;
$fail = 0;

function assert_true( bool $cond, string $msg ): void {
	global $pass, $fail;
	if ( $cond ) {
		echo "PASS $msg\n";
		++$pass;
	} else {
		echo "FAIL $msg\n";
		++$fail;
	}
}

$caps = \NGTBM\Domain\Authorization\CapabilityCatalog::ALL;
assert_true( in_array( 'ngt_cp_access', $caps, true ), 'cp access cap exists' );
assert_true( in_array( 'ngt_talent_evaluate', $caps, true ), 'talent evaluate cap exists' );

$matrix = \NGTBM\Domain\Authorization\RoleCatalog::access_matrix();
assert_true( count( $matrix['roles'] ) >= 9, 'role bundles registered' );
assert_true( ! empty( $matrix['matrix']['ngt_auditor']['ngt_audit_read'] ), 'auditor can read audit' );
assert_true( empty( $matrix['matrix']['ngt_auditor']['ngt_talent_configure'] ), 'auditor cannot configure talent' );
assert_true( ! empty( $matrix['matrix']['ngt_tutor_manager']['ngt_talent_evaluate'] ), 'tutor manager can evaluate' );

$reg = new \NGTBM\Domain\Subsystem\SubsystemRegistry();
$reg->register( [ 'id' => 'demo', 'name' => 'Demo', 'capabilities' => [ 'a.b' ] ] );
assert_true( $reg->get( 'demo' ) !== null, 'subsystem register' );
assert_true( count( $reg->to_list() ) === 1, 'subsystem list' );

$res = \NGTBM\Domain\Resource\ResourceCatalog::get( 'talent-evaluation' );
assert_true( is_array( $res ) && $res['permissions']['read'] === 'ngt_talent_read', 'talent resource schema' );

// Architecture: Beyond Measure must not require Companion includes.
$src = '';
$rii = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/src' ) );
foreach ( $rii as $file ) {
	if ( ! $file->isFile() || substr( $file->getFilename(), -4 ) !== '.php' ) {
		continue;
	}
	$src .= file_get_contents( $file->getPathname() );
}
assert_true( false === strpos( $src, 'NextGenTutors-Companion/includes' ), 'no Companion includes path coupling' );
assert_true( false === strpos( $src, "require_once NGC_" ), 'no NGC require_once coupling' );

echo "\n$pass PASS / $fail FAIL\n";
exit( $fail > 0 ? 1 : 0 );
