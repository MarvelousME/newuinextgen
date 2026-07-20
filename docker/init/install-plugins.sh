#!/bin/sh
# Install + activate NextGen fleet plugins (Companion, Plugin Manager, HTML Importer).
set -eu

WP_PATH="/var/www/html"
PLUGINS_DIR="${WP_PATH}/wp-content/plugins"

log() { printf '[newuinextgen-plugins] %s\n' "$1"; }

activate_plugin() {
	slug="$1"
	file="$2"
	if [ ! -f "${PLUGINS_DIR}/${file}" ]; then
		log "WARN: Missing ${file} — skip ${slug}"
		return 1
	fi
	log "Activating ${slug}..."
	wp plugin activate "$slug" --path="$WP_PATH" --allow-root
	return 0
}

COMPANION_OK=0
PM_OK=0
RHI_OK=0

if activate_plugin "NextGenTutors-Companion/nextgencompanion" "NextGenTutors-Companion/nextgencompanion.php"; then
	COMPANION_OK=1
	log "Seeding demo tutor roster (when NGC_ALLOW_DEMO_SEED)..."
	wp eval 'if ( class_exists( "NGC_Tutor_Seeder" ) ) { $r = NGC_Tutor_Seeder::ensure_seeded( true ); echo "seeded:" . json_encode( $r ) . "\n"; }' \
		--path="$WP_PATH" --allow-root 2>/dev/null || true

	log "Repairing page form shortcodes..."
	wp eval 'if ( class_exists( "NGC_Page_Forms_Registry" ) ) { $r = NGC_Page_Forms_Registry::ensure_production_forms( true ); echo json_encode( $r ) . "\n"; }' \
		--path="$WP_PATH" --allow-root 2>/dev/null || true

	log "Syncing launch pages + menus..."
	wp eval 'if ( function_exists( "bi_sync_launch_pages" ) ) { $r = bi_sync_launch_pages(); echo is_wp_error( $r ) ? $r->get_error_message() : json_encode( $r ); echo "\n"; }' \
		--path="$WP_PATH" --allow-root 2>/dev/null || true

	log "Verifying companion..."
	wp ngc verify --path="$WP_PATH" --allow-root 2>/dev/null || log "WARN: wp ngc verify reported issues (integrations may need extra plugins)"
fi

activate_plugin "NextGenTutors-Plugin-Manager/NextGenTutors-Plugin-Manager" "NextGenTutors-Plugin-Manager/NextGenTutors-Plugin-Manager.php" && PM_OK=1
activate_plugin "NextGenTutors-Html-Importer/revamp-html-importer" "NextGenTutors-Html-Importer/revamp-html-importer.php" && RHI_OK=1

activate_plugin "nextgen-automation-hub/nextgen-automation-hub" "nextgen-automation-hub/nextgen-automation-hub.php" 2>/dev/null || log "INFO: Automation Hub optional — mount ../nextgen-automation-hub"

activate_plugin "nextgen-command-center/nextgen-command-center" "nextgen-command-center/nextgen-command-center.php" 2>/dev/null || log "INFO: Command Center optional — mount content/_extracted/nextgen-command-center-v1.0"
activate_plugin "nextgen-completion-suite/nextgen-completion-suite" "nextgen-completion-suite/nextgen-completion-suite.php" 2>/dev/null || log "INFO: Completion Suite optional — mount content/_extracted/nextgen-completion-suite"

if [ "$COMPANION_OK" -eq 0 ]; then
	log "ERROR: NextGenTutors-Companion is required. Set COMPANION_PLUGIN_PATH in docker/.env or use the repo copy."
	exit 1
fi

log "Fleet ready — companion:${COMPANION_OK} plugin-manager:${PM_OK} html-importer:${RHI_OK}"
