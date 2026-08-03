<?php
if ( ! defined( 'NGT_AGENT_GATEWAY_URL' ) ) {
  define( 'NGT_AGENT_GATEWAY_URL', 'http://host.docker.internal:8787' );
}
if ( ! defined( 'NGT_GATEWAY_SHARED_SECRET' ) ) {
  define( 'NGT_GATEWAY_SHARED_SECRET', 'staging-local-secret' );
}
echo 'URL=' . NGC_Agent_Gateway_Client::base_url() . PHP_EOL;
$h = NGC_Agent_Gateway_Client::health();
if ( is_wp_error( $h ) ) {
  echo 'HEALTH_FAIL ' . $h->get_error_message() . PHP_EOL;
  exit(1);
}
echo 'HEALTH_OK mode=' . ( $h['a2a_mode'] ?? '' ) . PHP_EOL;
$t = NGC_Agent_Gateway_Client::submit_task( array(
  'agent_id' => 'ngt.firstparty.diagnostics',
  'message' => 'Subject expertise Mathematics Gauteng',
  'idempotency_key' => 'wp-live-' . time(),
) );
if ( is_wp_error( $t ) ) {
  echo 'TASK_FAIL ' . $t->get_error_code() . ' ' . $t->get_error_message() . PHP_EOL;
  exit(2);
}
echo 'TASK_OK status=' . ( $t['task']['status'] ?? '' ) . PHP_EOL;

$lead = NGC_Tutor_Leads::create( array(
  'source' => 'manual_entry',
  'subject' => 'Mathematics',
  'display_name' => 'Staging Lead Tutor',
  'public_email' => 'staging.tutor.lead+' . time() . '@example.com',
  'service_area' => 'Gauteng',
  'lawful_basis' => 'consent',
  'consent_status' => 'recorded',
  'discovery_query' => array( 'subject' => 'Mathematics', 'service_area' => 'Gauteng' ),
) );
if ( is_wp_error( $lead ) ) { echo 'LEAD_FAIL ' . $lead->get_error_message() . PHP_EOL; exit(3); }
echo 'LEAD_OK id=' . $lead['id'] . PHP_EOL;
$sync = NGC_Tutor_Leads::sync_fluentcrm( $lead['id'] );
if ( is_wp_error( $sync ) ) { echo 'CRM_FAIL ' . $sync->get_error_code() . ' ' . $sync->get_error_message() . PHP_EOL; exit(4); }
echo 'CRM_OK contact=' . (int) ( $sync['crm']['contact_id'] ?? 0 ) . PHP_EOL;

// Idempotent re-sync
$sync2 = NGC_Tutor_Leads::sync_fluentcrm( $lead['id'] );
if ( is_wp_error( $sync2 ) ) { echo 'CRM_RESYNC_FAIL' . PHP_EOL; exit(5); }
echo 'CRM_RESYNC_OK contact=' . (int) ( $sync2['crm']['contact_id'] ?? 0 ) . PHP_EOL;
