<?php
/**
 * Concrete provisioning steps (orders 1–32).
 *
 * Each step wraps verified Companion/theme APIs — no fabricated plugin installs
 * or invented secrets.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 1 — Environment preflight.
 */
class NGC_Provision_Step_Env_Preflight extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'env-preflight'; }
	public function label(): string { return 'Environment preflight'; }
	public function order(): int { return 1; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$blocking = [];
		$warnings = [];
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			$blocking[] = 'PHP < 8.0';
		}
		if ( ! defined( 'NGC_VERSION' ) ) {
			$blocking[] = 'Companion inactive';
		}
		$theme = wp_get_theme();
		if ( false === stripos( $theme->get_stylesheet(), 'beyondinfinity' ) && false === stripos( (string) $theme->get( 'Name' ), 'BeyondInfinity' ) ) {
			$blocking[] = 'Theme is not NextGenTutors-BeyondInfinity';
		}
		if ( ! is_writable( WP_CONTENT_DIR ) ) {
			$warnings[] = 'wp-content not writable';
		}
		$disk = @disk_free_space( ABSPATH );
		if ( false !== $disk && $disk < 50 * 1024 * 1024 ) {
			$warnings[] = 'Low disk space (<50MB free on ABSPATH volume)';
		}
		$evidence = [
			'php'      => PHP_VERSION,
			'wp'       => get_bloginfo( 'version' ),
			'theme'    => $theme->get_stylesheet(),
			'timezone' => wp_timezone_string(),
			'siteurl'  => site_url(),
			'home'     => home_url(),
			'env'      => $context->environment,
		];
		if ( $blocking ) {
			return $this->failed( implode( '; ', $blocking ), $evidence + [ 'blocking' => $blocking, 'warnings' => $warnings ] );
		}
		return $warnings
			? $this->partial( implode( '; ', $warnings ), $evidence + [ 'warnings' => $warnings ] )
			: $this->ok( 'Environment ready', $evidence );
	}

	public function verify( NGC_Provision_Context $context ): NGC_Provision_Check_Result {
		$r = $this->apply( $context );
		return new NGC_Provision_Check_Result( $r->ok, $r->ok ? [] : [ $r->message ], [], $r->evidence );
	}
}

/**
 * 2 — Backups awareness (does not invent backups; records expectation).
 */
class NGC_Provision_Step_Backup_Awareness extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'backups'; }
	public function label(): string { return 'Backups and restore validation'; }
	public function order(): int { return 2; }
	public function dependencies(): array { return [ 'env-preflight' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$scripts = [
			'docker/scripts/backup-wp.ps1',
			'scripts/backup-database.ps1',
		];
		$found = [];
		$root  = dirname( NGC_PLUGIN_DIR, 2 );
		foreach ( $scripts as $rel ) {
			$path = $root . '/' . $rel;
			if ( file_exists( $path ) ) {
				$found[] = $rel;
			}
		}
		$msg = $found
			? 'Backup scripts present; operator must prove restore before production apply.'
			: 'UNVERIFIED — backup scripts not found adjacent to Companion; use host backup before production.';
		return $this->partial( $msg, [ 'scripts_found' => $found, 'production' => $context->is_production() ] );
	}
}

/**
 * 3 — WordPress baseline.
 */
class NGC_Provision_Step_Wp_Baseline extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'wordpress-baseline'; }
	public function label(): string { return 'WordPress baseline'; }
	public function order(): int { return 3; }
	public function dependencies(): array { return [ 'env-preflight' ]; }

	public function plan( NGC_Provision_Context $context ): NGC_Provision_Change_Set {
		$set = new NGC_Provision_Change_Set();
		$set->updates[] = [ 'option' => 'timezone_string', 'to' => 'Africa/Johannesburg' ];
		$set->updates[] = [ 'option' => 'permalink_structure', 'to' => '/%postname%/' ];
		$set->updates[] = [ 'option' => 'date_format', 'to' => 'Y-m-d' ];
		$set->updates[] = [ 'option' => 'time_format', 'to' => 'H:i' ];
		$set->updates[] = [ 'option' => 'WPLANG', 'to' => 'en_ZA' ];
		return $set;
	}

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$previous = [
			'timezone_string'     => get_option( 'timezone_string' ),
			'permalink_structure' => get_option( 'permalink_structure' ),
			'date_format'         => get_option( 'date_format' ),
			'time_format'         => get_option( 'time_format' ),
		];
		if ( $context->dry_run ) {
			return $this->ok( 'Dry-run: WordPress baseline plan only', [ 'previous' => $previous, 'plan' => $this->plan( $context )->to_array() ] );
		}
		update_option( 'timezone_string', 'Africa/Johannesburg' );
		update_option( 'date_format', 'Y-m-d' );
		update_option( 'time_format', 'H:i' );
		update_option( 'permalink_structure', '/%postname%/' );
		flush_rewrite_rules( false );
		return $this->ok(
			'WordPress baseline applied',
			[
				'previous' => $previous,
				'current'  => [
					'timezone_string'     => get_option( 'timezone_string' ),
					'permalink_structure' => get_option( 'permalink_structure' ),
				],
			]
		);
	}

	public function verify( NGC_Provision_Context $context ): NGC_Provision_Check_Result {
		$tz = (string) get_option( 'timezone_string' );
		$ok = 'Africa/Johannesburg' === $tz;
		return new NGC_Provision_Check_Result( $ok, $ok ? [] : [ 'timezone_string is not Africa/Johannesburg' ], [], [ 'timezone_string' => $tz ] );
	}

	public function rollback( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$prev = $context->state['previous'] ?? null;
		if ( ! is_array( $prev ) ) {
			return $this->skipped( 'No previous snapshot stored for rollback' );
		}
		foreach ( [ 'timezone_string', 'permalink_structure', 'date_format', 'time_format' ] as $key ) {
			if ( isset( $prev[ $key ] ) ) {
				update_option( $key, $prev[ $key ] );
			}
		}
		flush_rewrite_rules( false );
		return $this->ok( 'WordPress baseline rolled back from snapshot', [ 'restored' => $prev ] );
	}
}

/**
 * 4 — Theme validation.
 */
class NGC_Provision_Step_Theme extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'theme'; }
	public function label(): string { return 'Theme installation'; }
	public function order(): int { return 4; }
	public function dependencies(): array { return [ 'wordpress-baseline' ]; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$theme = wp_get_theme();
		$ok = false !== stripos( $theme->get_stylesheet(), 'beyondinfinity' )
			|| false !== stripos( (string) $theme->get( 'Name' ), 'BeyondInfinity' );
		$evidence = [
			'stylesheet' => $theme->get_stylesheet(),
			'name'       => $theme->get( 'Name' ),
			'version'    => $theme->get( 'Version' ),
		];
		return $ok
			? $this->ok( 'NextGenTutors-BeyondInfinity active', $evidence )
			: $this->failed( 'BeyondInfinity theme not active — activate via Appearance → Themes or Docker setup.', $evidence );
	}
}

/**
 * 5 — First-party plugins detect.
 */
class NGC_Provision_Step_First_Party_Plugins extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'first-party-plugins'; }
	public function label(): string { return 'First-party plugin installation'; }
	public function order(): int { return 5; }
	public function dependencies(): array { return [ 'theme' ]; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$matrix = NGC_Provisioning_Engine::plugin_matrix( 'first-party' );
		$missing = array_values( array_filter( $matrix, static function ( $row ) {
			return empty( $row['active'] );
		} ) );
		$evidence = [ 'plugins' => $matrix ];
		if ( $missing ) {
			return $this->partial(
				'Some first-party plugins inactive — install via Plugin Manager / approved ZIPs (detect-only).',
				$evidence + [ 'inactive' => $missing ]
			);
		}
		return $this->ok( 'First-party plugins active', $evidence );
	}
}

/**
 * 6 — Third-party detection.
 */
class NGC_Provision_Step_Third_Party_Detect extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'third-party-detect'; }
	public function label(): string { return 'Third-party dependency detection'; }
	public function order(): int { return 6; }
	public function dependencies(): array { return [ 'first-party-plugins' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$matrix = NGC_Provisioning_Engine::plugin_matrix( 'third-party' );
		return $this->partial( 'Third-party matrix recorded (detect-only; no silent ZIP overwrite).', [ 'plugins' => $matrix ] );
	}
}

/**
 * 7 — Third-party install note.
 */
class NGC_Provision_Step_Third_Party_Install extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'third-party-install'; }
	public function label(): string { return 'Third-party installation/activation'; }
	public function order(): int { return 7; }
	public function dependencies(): array { return [ 'third-party-detect' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		return $this->skipped(
			'Install uses Plugin Manager offline-packages or licensed sources only — never invent download URLs.',
			[ 'hint' => 'wp-admin → NextGen Plugin Manager / docker/init/install-*.sh' ]
		);
	}
}

/**
 * 8 — Database migrations.
 */
class NGC_Provision_Step_Migrations extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'migrations'; }
	public function label(): string { return 'Database migrations'; }
	public function order(): int { return 8; }
	public function dependencies(): array { return [ 'first-party-plugins' ]; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		if ( $context->dry_run ) {
			return $this->ok( 'Dry-run: would run NGC_Database::create_tables and NGTAI migrator if present' );
		}
		$done = [];
		if ( class_exists( 'NGC_Database' ) ) {
			NGC_Database::create_tables();
			$done[] = 'ngc_tables';
		}
		if ( class_exists( 'NGTAI_Migrator' ) && method_exists( 'NGTAI_Migrator', 'migrate' ) ) {
			NGTAI_Migrator::migrate();
			$done[] = 'ngtai_migrate';
		} elseif ( class_exists( 'NGTAI_Migrator' ) && method_exists( 'NGTAI_Migrator', 'maybe_upgrade' ) ) {
			NGTAI_Migrator::maybe_upgrade();
			$done[] = 'ngtai_maybe_upgrade';
		}
		return $this->ok( 'Migrations executed', [ 'actions' => $done ] );
	}
}

/**
 * 9 — Roles.
 */
class NGC_Provision_Step_Roles extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'roles'; }
	public function label(): string { return 'Roles and capabilities'; }
	public function order(): int { return 9; }
	public function dependencies(): array { return [ 'migrations' ]; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		if ( $context->dry_run ) {
			return $this->ok( 'Dry-run: would call NGC_Roles::install()' );
		}
		if ( ! class_exists( 'NGC_Roles' ) ) {
			return $this->failed( 'NGC_Roles missing' );
		}
		NGC_Roles::install();
		$roles = [ 'parent', 'parent_guardian', 'student', 'tutor', 'tutor_applicant', 'ngc_finance', 'ngc_safeguarding', 'ngc_support', 'ngc_operations' ];
		$present = [];
		foreach ( $roles as $role ) {
			$present[ $role ] = (bool) get_role( $role );
		}
		return $this->ok( 'Roles installed', [ 'roles' => $present ] );
	}
}

/**
 * 10 — Business profile.
 */
class NGC_Provision_Step_Business_Profile extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'business-profile'; }
	public function label(): string { return 'Business profile'; }
	public function order(): int { return 10; }
	public function dependencies(): array { return [ 'roles' ]; }

	public function plan( NGC_Provision_Context $context ): NGC_Provision_Change_Set {
		$set = new NGC_Provision_Change_Set();
		if ( class_exists( 'NGC_Business_Profile' ) ) {
			$set->meta['diff'] = NGC_Business_Profile::diff();
		}
		return $set;
	}

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		if ( ! class_exists( 'NGC_Business_Profile' ) ) {
			return $this->failed( 'NGC_Business_Profile missing' );
		}
		if ( $context->dry_run ) {
			return $this->ok( 'Dry-run business profile diff', [ 'diff' => NGC_Business_Profile::diff() ] );
		}
		$result = NGC_Business_Profile::apply( $context->force_safe );
		$ok = ! empty( $result['ok'] );
		return $ok
			? $this->ok( 'Business profile applied', $result )
			: $this->failed( 'Business profile apply failed', $result );
	}

	public function verify( NGC_Provision_Context $context ): NGC_Provision_Check_Result {
		if ( ! class_exists( 'NGC_Business_Profile' ) ) {
			return new NGC_Provision_Check_Result( false, [ 'Business profile class missing' ] );
		}
		$status = NGC_Business_Profile::status();
		$ok = ! empty( $status['applied'] );
		return new NGC_Provision_Check_Result( $ok, $ok ? [] : [ 'Business profile not marked applied' ], [], $status );
	}
}

/**
 * 11 — UI library.
 */
class NGC_Provision_Step_Ui_Library extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'ui-library'; }
	public function label(): string { return 'Design tokens and UI library'; }
	public function order(): int { return 11; }
	public function dependencies(): array { return [ 'business-profile' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$active = class_exists( 'NGC_UI_Library' ) || class_exists( 'NGT_UI_Bootstrap' );
		$path = WP_CONTENT_DIR . '/ngt-ui-library';
		$on_disk = is_dir( $path );
		return ( $active || $on_disk )
			? $this->ok( 'UI library available', [ 'class_active' => $active, 'content_path' => $on_disk ] )
			: $this->partial( 'UI library not detected under wp-content/ngt-ui-library — extract release zip.', [ 'expected' => $path ] );
	}
}

/**
 * 12 — Pages/templates.
 */
class NGC_Provision_Step_Pages extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'pages'; }
	public function label(): string { return 'Pages and templates'; }
	public function order(): int { return 12; }
	public function dependencies(): array { return [ 'business-profile' ]; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		if ( $context->dry_run ) {
			return $this->ok( 'Dry-run: page sync skipped' );
		}
		$synced = false;
		if ( function_exists( 'bi_sync_production_pages' ) ) {
			bi_sync_production_pages();
			$synced = true;
		} elseif ( function_exists( 'nbi_sync_production_pages' ) ) {
			nbi_sync_production_pages();
			$synced = true;
		}
		$required = [ 'home', 'find-a-tutor', 'become-a-tutor', 'login', 'register', 'pricing', 'contact', 'privacy-policy' ];
		$found = [];
		foreach ( $required as $slug ) {
			$page = get_page_by_path( $slug );
			$found[ $slug ] = $page ? (int) $page->ID : 0;
		}
		$missing = array_keys( array_filter( $found, static function ( $id ) { return ! $id; } ) );
		$evidence = [ 'synced' => $synced, 'pages' => $found ];
		if ( $missing ) {
			return $this->partial( 'Some required pages missing: ' . implode( ', ', $missing ), $evidence );
		}
		return $this->ok( 'Required pages present', $evidence );
	}
}

/**
 * 13 — Menus.
 */
class NGC_Provision_Step_Menus extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'menus'; }
	public function label(): string { return 'Menus and navigation'; }
	public function order(): int { return 13; }
	public function dependencies(): array { return [ 'pages' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$locations = get_nav_menu_locations();
		$menus = wp_get_nav_menus();
		return $this->ok(
			'Navigation inventory recorded',
			[
				'locations' => $locations,
				'menu_count'=> is_array( $menus ) ? count( $menus ) : 0,
			]
		);
	}
}

/**
 * 14 — Forms registry.
 */
class NGC_Provision_Step_Forms extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'forms'; }
	public function label(): string { return 'Forms'; }
	public function order(): int { return 14; }
	public function dependencies(): array { return [ 'pages' ]; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$registry = class_exists( 'NGC_Page_Forms_Registry' );
		return $registry
			? $this->ok( 'Page forms registry available', [ 'class' => 'NGC_Page_Forms_Registry' ] )
			: $this->partial( 'Forms registry class not loaded', [] );
	}
}

/**
 * 15 — CRM readiness.
 */
class NGC_Provision_Step_Crm extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'crm'; }
	public function label(): string { return 'CRM'; }
	public function order(): int { return 15; }
	public function dependencies(): array { return [ 'forms' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$adapter = class_exists( 'NGC_Fluentcrm_Adapter' ) ? new NGC_Fluentcrm_Adapter() : null;
		$available = $adapter && method_exists( $adapter, 'is_available' ) && $adapter->is_available();
		if ( $available && ! $context->dry_run && method_exists( $adapter, 'bootstrap_assets' ) ) {
			$adapter->bootstrap_assets();
		}
		return $available
			? $this->ok( 'FluentCRM adapter available', [ 'available' => true ] )
			: $this->partial( 'FluentCRM not available — install/activate then re-run.', [ 'available' => false ] );
	}
}

/**
 * 16 — Email/SMTP readiness.
 */
class NGC_Provision_Step_Email extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'email'; }
	public function label(): string { return 'Email and SMTP readiness'; }
	public function order(): int { return 16; }
	public function dependencies(): array { return [ 'business-profile' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$smtp = NGC_Provisioning_Engine::is_plugin_active( 'fluent-smtp/fluent-smtp.php' );
		return $smtp
			? $this->partial( 'FluentSMTP active — configure credentials via admin (secrets never packaged).', [ 'fluent_smtp' => true ] )
			: $this->partial( 'FluentSMTP inactive — email delivery UNVERIFIED.', [ 'fluent_smtp' => false ] );
	}
}

/**
 * 17 — Tutor/student domain.
 */
class NGC_Provision_Step_Domain extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'domain-config'; }
	public function label(): string { return 'Tutor and student domain configuration'; }
	public function order(): int { return 17; }
	public function dependencies(): array { return [ 'roles', 'migrations' ]; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$types = get_post_types( [], 'names' );
		$has_tutors = isset( $types['tutors'] ) || post_type_exists( 'tutors' );
		return $has_tutors
			? $this->ok( 'Tutor CPT present', [ 'post_types' => array_values( array_intersect( array_keys( (array) $types ), [ 'tutors', 'tutor' ] ) ) ] )
			: $this->partial( 'Tutor CPT not registered yet — ensure Companion post types booted.', [] );
	}
}

/**
 * 18 — LMS.
 */
class NGC_Provision_Step_Lms extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'lms'; }
	public function label(): string { return 'LMS'; }
	public function order(): int { return 18; }
	public function dependencies(): array { return [ 'domain-config' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$active = NGC_Provisioning_Engine::is_plugin_active( 'masterstudy-lms-learning-management-system/masterstudy-lms-learning-management-system.php' )
			|| class_exists( 'NGC_Lms' );
		return $active
			? $this->partial( 'LMS surface present — do not fabricate curricula.', [ 'lms' => true ] )
			: $this->skipped( 'MasterStudy LMS not active', [ 'lms' => false ] );
	}
}

/**
 * 19 — Booking.
 */
class NGC_Provision_Step_Booking extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'booking'; }
	public function label(): string { return 'Booking'; }
	public function order(): int { return 19; }
	public function dependencies(): array { return [ 'domain-config' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$amelia = class_exists( 'NGC_Amelia_Bootstrap' ) && method_exists( 'NGC_Amelia_Bootstrap', 'is_active' )
			? NGC_Amelia_Bootstrap::is_active()
			: false;
		$bookings = class_exists( 'NGC_Bookings' );
		return ( $amelia || $bookings )
			? $this->partial( 'Booking layer present — rates/windows belong in INPUTS-REQUIRED.', [ 'amelia' => $amelia, 'ngc_bookings' => $bookings ] )
			: $this->partial( 'Booking layer incomplete', [ 'amelia' => $amelia, 'ngc_bookings' => $bookings ] );
	}
}

/**
 * 20 — Commerce readiness.
 */
class NGC_Provision_Step_Commerce extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'commerce'; }
	public function label(): string { return 'Commerce and payment gateway readiness'; }
	public function order(): int { return 20; }
	public function dependencies(): array { return [ 'business-profile' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$woo = class_exists( 'WooCommerce' ) || NGC_Provisioning_Engine::is_plugin_active( 'woocommerce/woocommerce.php' );
		$payfast = NGC_Provisioning_Engine::is_plugin_active( 'woocommerce-payfast-gateway/gateway.php' )
			|| NGC_Provisioning_Engine::is_plugin_active( 'woocommerce-payfast-gateway/woocommerce-payfast-gateway.php' )
			|| class_exists( 'NGC_PayFast' );
		if ( $woo && ! $context->dry_run && function_exists( 'update_option' ) ) {
			// Safe non-secret store basics only when Woo present.
			update_option( 'woocommerce_default_country', 'ZA' );
			update_option( 'woocommerce_currency', 'ZAR' );
		}
		return $this->partial(
			'Commerce readiness recorded — PayFast credentials must be entered in admin; never packaged.',
			[ 'woocommerce' => $woo, 'payfast_surface' => $payfast ]
		);
	}
}

/**
 * 21 — Products (no invented prices).
 */
class NGC_Provision_Step_Products extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'products'; }
	public function label(): string { return 'Products, packages, and pricing'; }
	public function order(): int { return 21; }
	public function dependencies(): array { return [ 'commerce' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		return $this->skipped(
			'Product/price creation blocked until approved pricing is supplied in INPUTS-REQUIRED.md.',
			[ 'reason' => 'no_invented_prices' ]
		);
	}
}

/**
 * 22 — Wallet/ledger.
 */
class NGC_Provision_Step_Finance extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'finance'; }
	public function label(): string { return 'Wallet, ledger, invoices, refunds, and payouts'; }
	public function order(): int { return 22; }
	public function dependencies(): array { return [ 'migrations', 'commerce' ]; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		global $wpdb;
		$tables = [
			'wallet_ledger' => $wpdb->prefix . 'ngc_wallet_ledger',
			'invoices'      => $wpdb->prefix . 'ngc_invoices',
			'payouts'       => $wpdb->prefix . 'ngc_payouts',
			'earnings'      => $wpdb->prefix . 'ngc_earnings',
		];
		$present = [];
		foreach ( $tables as $key => $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$present[ $key ] = ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table );
		}
		$ok = ! in_array( false, $present, true );
		return $ok
			? $this->ok( 'Finance tables present', $present )
			: $this->partial( 'Some finance tables missing — run migrations.', $present );
	}
}

/**
 * 23 — Workflows.
 */
class NGC_Provision_Step_Workflows extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'workflows'; }
	public function label(): string { return 'Workflow automation'; }
	public function order(): int { return 23; }
	public function dependencies(): array { return [ 'forms', 'migrations' ]; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$orch = class_exists( 'NGC_Workflow_Orchestrator' );
		$studio = class_exists( 'NGC_Studio' );
		return ( $orch || $studio )
			? $this->ok( 'Workflow runtime present', [ 'orchestrator' => $orch, 'studio' => $studio ] )
			: $this->failed( 'No workflow runtime class found' );
	}
}

/**
 * 24 — Gamification.
 */
class NGC_Provision_Step_Gamification extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'gamification'; }
	public function label(): string { return 'Gamification'; }
	public function order(): int { return 24; }
	public function dependencies(): array { return [ 'domain-config' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$gami = class_exists( 'NGC_Gamification' ) || class_exists( 'NGC_Gamipress_Adapter' );
		return $gami
			? $this->partial( 'Gamification surface present — configure only approved educational outcomes.', [ 'available' => true ] )
			: $this->skipped( 'Gamification not loaded', [ 'available' => false ] );
	}
}

/**
 * 25 — Analytics.
 */
class NGC_Provision_Step_Analytics extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'analytics'; }
	public function label(): string { return 'Analytics and attribution'; }
	public function order(): int { return 25; }
	public function dependencies(): array { return [ 'migrations' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$tracking = class_exists( 'NGC_Platform_Tracking' );
		return $tracking
			? $this->ok( 'Platform tracking available', [ 'class' => 'NGC_Platform_Tracking' ] )
			: $this->partial( 'Platform tracking class missing', [] );
	}
}

/**
 * 26 — AI Integration.
 */
class NGC_Provision_Step_Ai extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'ai-integration'; }
	public function label(): string { return 'AI Integration'; }
	public function order(): int { return 26; }
	public function dependencies(): array { return [ 'first-party-plugins' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$ngtai = defined( 'NGTAI_VERSION' ) || NGC_Provisioning_Engine::is_plugin_active( 'NextGenTutors-AI-Integration/nextgentutors-ai-integration.php' );
		return $ngtai
			? $this->partial( 'AI Integration active — BYOK secrets via admin; consequential actions require human approval.', [ 'ngtai' => true ] )
			: $this->partial( 'AI Integration inactive', [ 'ngtai' => false ] );
	}
}

/**
 * 27 — Mission Control.
 */
class NGC_Provision_Step_Mission_Control extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'mission-control'; }
	public function label(): string { return 'Mission Control'; }
	public function order(): int { return 27; }
	public function dependencies(): array { return [ 'first-party-plugins' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$mc = NGC_Provisioning_Engine::is_plugin_active( 'NextGenTutors-Mission-Control/nextgentutors-mission-control.php' )
			|| class_exists( 'NGTMC_Plugin' );
		return $mc
			? $this->ok( 'Mission Control available', [ 'active' => true ] )
			: $this->partial( 'Mission Control inactive', [ 'active' => false ] );
	}
}

/**
 * 28 — Demo journeys (non-production).
 */
class NGC_Provision_Step_Demo extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'demo-journeys'; }
	public function label(): string { return 'Relational demo journeys'; }
	public function order(): int { return 28; }
	public function dependencies(): array { return [ 'finance', 'workflows', 'pages' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		if ( $context->is_production() && ! $context->allow_demo ) {
			return $this->skipped( 'Demo seeding refused in production without allow_demo.', [ 'environment' => $context->environment ] );
		}
		if ( ! $context->allow_demo && 'local' !== $context->environment && 'demo' !== $context->environment ) {
			return $this->skipped( 'Demo seeding not requested for this environment.', [ 'environment' => $context->environment ] );
		}
		if ( $context->dry_run ) {
			return $this->ok( 'Dry-run: would seed Phase 14 demo data' );
		}
		if ( ! class_exists( 'NGC_Demo_Seeder' ) ) {
			return $this->failed( 'NGC_Demo_Seeder missing' );
		}
		if ( class_exists( 'NGC_Demo_Env' ) ) {
			NGC_Demo_Env::set_demo_mode( true );
		}
		$graph = NGC_Demo_Seeder::seed( 'all' );
		if ( is_wp_error( $graph ) ) {
			return $this->failed( $graph->get_error_message() );
		}

		$seed_version = (string) (
			$graph['seed_version']
			?? ( class_exists( 'NGC_Demo_Env' ) ? NGC_Demo_Env::SEED_VERSION : '' )
		);
		$errors       = is_array( $graph['errors'] ?? null ) ? $graph['errors'] : [];
		$evidence     = [
			'bookings'         => $graph['bookings'] ?? [],
			'seed_version'     => $seed_version,
			'scenario'         => $graph['scenario'] ?? 'all',
			'errors'           => $errors,
			'journey_catalogue'=> class_exists( 'NGC_Demo_Journeys' ) ? count( NGC_Demo_Journeys::list_journeys() ) : 0,
			'catalogue_dir'    => class_exists( 'NGC_Demo_Journeys' ) ? NGC_Demo_Journeys::catalogue_dir() : null,
		];

		if ( ! empty( $errors ) ) {
			return $this->partial( 'Demo seed completed with errors', $evidence + $graph );
		}
		if ( '' === $seed_version ) {
			return $this->failed( 'Demo seed completed but seed_version is missing', $evidence );
		}

		return $this->ok( 'Demo seed completed', $evidence );
	}

	/**
	 * Post-seed verification must not report ok while the demo verifier fails.
	 *
	 * @param NGC_Provision_Context $context Context.
	 * @return NGC_Provision_Check_Result
	 */
	public function verify( NGC_Provision_Context $context ): NGC_Provision_Check_Result {
		if ( $context->dry_run ) {
			return new NGC_Provision_Check_Result( true, [], [ 'dry-run' ], [] );
		}
		if ( $context->is_production() && ! $context->allow_demo ) {
			return new NGC_Provision_Check_Result( true, [], [ 'demo not required in production' ], [] );
		}
		if ( ! $context->allow_demo && 'local' !== $context->environment && 'demo' !== $context->environment ) {
			return new NGC_Provision_Check_Result( true, [], [ 'demo not requested' ], [] );
		}
		if ( ! class_exists( 'NGC_Demo_Verifier' ) ) {
			return new NGC_Provision_Check_Result( false, [ 'NGC_Demo_Verifier missing' ] );
		}

		$report = NGC_Demo_Verifier::verify();
		$ok     = ! empty( $report['ok'] );
		$seed   = is_array( $report['seed'] ?? null ) ? $report['seed'] : [];
		$version = (string) ( $seed['version'] ?? '' );
		if ( $ok && '' === $version && class_exists( 'NGC_Demo_Env' ) ) {
			$version = NGC_Demo_Env::SEED_VERSION;
		}
		if ( $ok && ( '' === $version || ( class_exists( 'NGC_Demo_Env' ) && NGC_Demo_Env::SEED_VERSION !== $version ) ) ) {
			$ok = false;
			$report['failures'][] = 'seed_version mismatch or missing (expected ' . ( class_exists( 'NGC_Demo_Env' ) ? NGC_Demo_Env::SEED_VERSION : 'set' ) . ', got ' . ( $version !== '' ? $version : 'null' ) . ')';
		}

		return new NGC_Provision_Check_Result(
			$ok,
			$ok ? [] : array_values( array_map( 'strval', $report['failures'] ?? [ 'Demo verification failed' ] ) ),
			[],
			[
				'demo'         => $report,
				'seed_version' => $version !== '' ? $version : null,
			]
		);
	}

	public function rollback( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		if ( ! class_exists( 'NGC_Demo_Reset' ) ) {
			return $this->failed( 'Demo reset unavailable' );
		}
		$r = NGC_Demo_Reset::reset( 'all' );
		if ( is_wp_error( $r ) ) {
			return $this->failed( $r->get_error_message() );
		}
		return $this->ok( 'Demo data reset', is_array( $r ) ? $r : [] );
	}
}

/**
 * 29 — Verification + evidence.
 */
class NGC_Provision_Step_Verify extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'verification'; }
	public function label(): string { return 'Verification and evidence'; }
	public function order(): int { return 29; }
	public function dependencies(): array { return [ 'business-profile', 'migrations' ]; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$report = [
			'business' => class_exists( 'NGC_Business_Profile' ) ? NGC_Business_Profile::status() : null,
			'theme_ok' => false !== stripos( wp_get_theme()->get_stylesheet(), 'beyondinfinity' ),
		];
		if ( class_exists( 'NGC_Verification' ) ) {
			$report['companion'] = NGC_Verification::run_checks();
		}

		$demo_required = $context->allow_demo || 'local' === $context->environment || 'demo' === $context->environment;
		if ( class_exists( 'NGC_Demo_Verifier' ) && $demo_required && ! $context->is_production() ) {
			$report['demo'] = NGC_Demo_Verifier::verify();
		}

		$blocking = [];
		if ( empty( $report['theme_ok'] ) ) {
			$blocking[] = 'BeyondInfinity theme not active';
		}
		if ( ! empty( $report['companion'] ) && empty( $report['companion']['ok'] ) ) {
			$blocking[] = 'Companion verification failed';
		}
		if ( isset( $report['demo'] ) && empty( $report['demo']['ok'] ) ) {
			$failures = is_array( $report['demo']['failures'] ?? null ) ? $report['demo']['failures'] : [ 'Demo verification failed' ];
			foreach ( $failures as $failure ) {
				$blocking[] = 'Demo: ' . $failure;
			}
		}

		$dir = WP_CONTENT_DIR . '/uploads/ngt-provisioning';
		wp_mkdir_p( $dir );
		$path = $dir . '/verify-' . gmdate( 'Ymd-His' ) . '.json';
		$report['evidence_path'] = $path;
		$report['blocking']      = $blocking;
		file_put_contents( $path, wp_json_encode( $report, JSON_PRETTY_PRINT ) ); // phpcs:ignore

		$ok = empty( $blocking );
		return $ok
			? $this->ok( 'Verification passed', $report )
			: $this->failed( 'Verification failed: ' . implode( '; ', $blocking ), $report );
	}

	/**
	 * @param NGC_Provision_Context $context Context.
	 * @return NGC_Provision_Check_Result
	 */
	public function verify( NGC_Provision_Context $context ): NGC_Provision_Check_Result {
		$blocking = [];
		$theme_ok = false !== stripos( wp_get_theme()->get_stylesheet(), 'beyondinfinity' );
		if ( ! $theme_ok ) {
			$blocking[] = 'BeyondInfinity theme not active';
		}
		if ( class_exists( 'NGC_Verification' ) ) {
			$companion = NGC_Verification::run_checks();
			if ( empty( $companion['ok'] ) ) {
				$blocking[] = 'Companion verification failed';
			}
		}
		$demo_required = $context->allow_demo || 'local' === $context->environment || 'demo' === $context->environment;
		$demo          = null;
		if ( class_exists( 'NGC_Demo_Verifier' ) && $demo_required && ! $context->is_production() ) {
			$demo = NGC_Demo_Verifier::verify();
			if ( empty( $demo['ok'] ) ) {
				foreach ( (array) ( $demo['failures'] ?? [ 'Demo verification failed' ] ) as $failure ) {
					$blocking[] = 'Demo: ' . $failure;
				}
			}
		}
		return new NGC_Provision_Check_Result(
			empty( $blocking ),
			$blocking,
			[],
			[ 'theme_ok' => $theme_ok, 'demo' => $demo ]
		);
	}
}

/**
 * 30 — Production hardening checks.
 */
class NGC_Provision_Step_Hardening extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'hardening'; }
	public function label(): string { return 'Production hardening'; }
	public function order(): int { return 30; }
	public function dependencies(): array { return [ 'verification' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$checks = [
			'wp_debug'        => ! ( defined( 'WP_DEBUG' ) && WP_DEBUG && $context->is_production() ),
			'https'           => is_ssl() || ! $context->is_production(),
			'demo_mode_off'   => ! $context->is_production() || ( class_exists( 'NGC_Demo_Env' ) ? ! NGC_Demo_Env::is_demo_mode() : true ),
			'users_can_register' => true, // informational
		];
		$warnings = [];
		if ( $context->is_production() && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$warnings[] = 'WP_DEBUG enabled on production environment detection';
		}
		if ( $context->is_production() && ! is_ssl() ) {
			$warnings[] = 'HTTPS not detected';
		}
		return $warnings
			? $this->partial( implode( '; ', $warnings ), [ 'checks' => $checks, 'warnings' => $warnings ] )
			: $this->ok( 'Hardening checks recorded', [ 'checks' => $checks ] );
	}
}

/**
 * 31 — Packaging awareness.
 */
class NGC_Provision_Step_Packaging extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'packaging'; }
	public function label(): string { return 'Packaging and release manifest'; }
	public function order(): int { return 31; }
	public function dependencies(): array { return [ 'verification' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		// Monorepo checkout: Companion lives at <repo>/NextGenTutors-Companion.
		// Packaged install: release artifacts may be under wp-content or absent.
		// Prefer the richest valid manifest — never treat an empty stub as COMPLETED.
		$roots = array_unique(
			array_filter(
				[
					dirname( NGC_PLUGIN_DIR ),
					dirname( NGC_PLUGIN_DIR, 2 ),
					dirname( NGC_PLUGIN_DIR, 3 ),
					WP_CONTENT_DIR,
					ABSPATH,
				]
			)
		);
		$searched   = [];
		$candidates = [];
		foreach ( $roots as $root ) {
			foreach ( [ '/release/release-manifest.json', '/dist/release-manifest.json', '/release-manifest.json' ] as $rel ) {
				$path = $root . $rel;
				$searched[] = $path;
				if ( ! file_exists( $path ) ) {
					continue;
				}
				$raw  = file_get_contents( $path );
				// PowerShell ConvertTo-Json often writes a UTF-8 BOM; strip so json_decode works.
				if ( is_string( $raw ) && strncmp( $raw, "\xEF\xBB\xBF", 3 ) === 0 ) {
					$raw = substr( $raw, 3 );
				}
				$json = is_string( $raw ) ? json_decode( $raw, true ) : null;
				$pkgs = ( is_array( $json ) && isset( $json['packages'] ) && is_array( $json['packages'] ) )
					? $json['packages']
					: [];
				$names = [];
				foreach ( $pkgs as $pkg ) {
					if ( ! empty( $pkg['name'] ) ) {
						$names[] = (string) $pkg['name'];
					}
				}
				$has_mc = in_array( 'NextGenTutors-Mission-Control', $names, true );
				$has_ui = in_array( 'ngt-ui-library', $names, true );
				$candidates[] = [
					'path'                     => $path,
					'packages'                 => $names,
					'package_count'            => count( $names ),
					'includes_mission_control' => $has_mc,
					'includes_ui_library'      => $has_ui,
					'score'                    => count( $names ) + ( $has_mc ? 10 : 0 ) + ( $has_ui ? 10 : 0 ),
				];
			}
		}

		usort(
			$candidates,
			static function ( $a, $b ) {
				return (int) $b['score'] <=> (int) $a['score'];
			}
		);
		$best = $candidates[0] ?? null;

		// Host build may exist even when WP runtime cannot see the monorepo release/ tree
		// (typical Docker bind-mount of plugins/ only). Do not imply Mission Control / ui-library
		// are missing from build-release.ps1 — that pipeline is verified on the host.
		if ( $best && $best['includes_mission_control'] && $best['includes_ui_library'] ) {
			return $this->ok(
				'Release manifest found (includes Mission Control + ui-library)',
				[
					'path'                     => $best['path'],
					'packages'                 => $best['packages'],
					'includes_mission_control' => true,
					'includes_ui_library'      => true,
					'candidates_scanned'       => count( $candidates ),
				]
			);
		}

		if ( $best && $best['package_count'] > 0 ) {
			return $this->partial(
				'Release manifest found but incomplete for TD-04/TD-05 (need NextGenTutors-Mission-Control + ngt-ui-library packages). Host scripts/build-release.ps1 emits the full set — mount release/ or copy the host release-manifest.json into the WP runtime.',
				[
					'path'                     => $best['path'],
					'packages'                 => $best['packages'],
					'includes_mission_control' => $best['includes_mission_control'],
					'includes_ui_library'      => $best['includes_ui_library'],
					'searched'                 => $searched,
					'note'                     => 'Incomplete runtime stub must not be reported as packaging COMPLETED.',
				]
			);
		}

		if ( $best ) {
			return $this->partial(
				'Release manifest stub found with empty packages list at ' . $best['path'] . '. Host pipeline scripts/build-release.ps1 already packages Mission Control + ngt-ui-library; replace this stub or mount the monorepo release/ tree.',
				[
					'path'                     => $best['path'],
					'packages'                 => [],
					'includes_mission_control' => false,
					'includes_ui_library'      => false,
					'searched'                 => $searched,
					'note'                     => 'TD-04/TD-05 release zip inclusion is a host build concern; empty stubs are not success.',
				]
			);
		}

		return $this->partial(
			'Release manifest not visible in this WordPress runtime path. Host pipeline scripts/build-release.ps1 already packages Mission Control + ngt-ui-library into release/ and dist/; mount the monorepo release/ folder or copy release-manifest.json into wp-content to verify here.',
			[
				'searched' => $searched,
				'note'     => 'TD-04/TD-05 release zip inclusion is a host build concern, not implied missing by this runtime warning.',
			]
		);
	}
}

/**
 * 32 — Deployment documentation pointer.
 */
class NGC_Provision_Step_Deployment_Docs extends NGC_Provisioning_Step_Base {
	public function id(): string { return 'deployment-docs'; }
	public function label(): string { return 'Deployment and rollback documentation'; }
	public function order(): int { return 32; }
	public function dependencies(): array { return [ 'packaging' ]; }
	public function is_critical(): bool { return false; }

	public function apply( NGC_Provision_Context $context ): NGC_Provision_Step_Result {
		$roots = array_unique(
			array_filter(
				[
					dirname( NGC_PLUGIN_DIR ),
					dirname( NGC_PLUGIN_DIR, 2 ),
					dirname( NGC_PLUGIN_DIR, 3 ),
					WP_CONTENT_DIR,
					ABSPATH,
				]
			)
		);
		$docs = [
			'docs/COMMERCIAL-DEPLOYMENT-GUIDE.md',
			'docs/PRODUCTION-READINESS.md',
			'docs/tutorials/BEYONDINFINITY-SEQUENTIAL-SETUP.md',
			'release/documentation/06-DEPLOYMENT-GUIDE.md',
		];
		$present = [];
		foreach ( $docs as $rel ) {
			$hit = false;
			foreach ( $roots as $root ) {
				if ( file_exists( $root . '/' . $rel ) ) {
					$hit = true;
					break;
				}
			}
			$present[ $rel ] = $hit;
		}
		return $this->ok( 'Deployment documentation inventory', [ 'docs' => $present ] );
	}
}
