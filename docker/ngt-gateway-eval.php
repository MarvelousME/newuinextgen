<?php
$h = NGC_Agent_Gateway_Client::health();
if ( is_wp_error( $h ) ) {
  echo 'HEALTH_FAIL ' . $h->get_error_message() . PHP_EOL;
  exit(1);
}
echo 'HEALTH_OK mode=' . ( $h['a2a_mode'] ?? '' ) . PHP_EOL;
$t = NGC_Agent_Gateway_Client::submit_task( array(
  'agent_id' => 'ngt.firstparty.diagnostics',
  'message' => 'Subject expertise Mathematics Gauteng',
  'idempotency_key' => 'wp-live-1',
) );
if ( is_wp_error( $t ) ) {
  echo 'TASK_FAIL ' . $t->get_error_code() . ' ' . $t->get_error_message() . PHP_EOL;
  exit(2);
}
echo 'TASK_OK status=' . ( $t['task']['status'] ?? '' ) . PHP_EOL;
