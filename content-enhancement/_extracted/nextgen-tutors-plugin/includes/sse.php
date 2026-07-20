<?php
/**
 * Server‑Sent Events endpoint for real‑time analytics.
 * This file defines the callback used by the REST route /ngt/v1/stream.
 */
if (!defined('ABSPATH')) {
    exit;
}

function ngt_sse_stream( WP_REST_Request $request ) {
    // Set appropriate headers for SSE.
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');

    // Retrieve the last event ID the client received (if any).
    $last_id = $request->get_param('last_id');
    $last_id = $last_id ? intval($last_id) : 0;

    $start_time = time();
    $max_duration = 30; // seconds – keep connection alive for 30 s then close.
    $sleep_interval = 2; // seconds between polls.

    while ( (time() - $start_time) < $max_duration ) {
        global $wpdb;
        $table = $wpdb->prefix . 'ngt_logs';
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, event_type, payload, created_at FROM $table WHERE id > %d ORDER BY id ASC",
                $last_id
            ),
            OBJECT
        );
        if ( $results ) {
            foreach ( $results as $row ) {
                $data = [
                    'id'          => $row->id,
                    'type'        => $row->event_type,
                    'payload'     => json_decode( $row->payload, true ),
                    'timestamp'   => $row->created_at,
                ];
                echo "id: {$row->id}\n";
                echo "event: {$row->event_type}\n";
                echo "data: " . json_encode( $data ) . "\n\n";
                // Flush the output buffer so client receives the event immediately.
                @ob_flush();
                @flush();
                $last_id = $row->id;
            }
        }
        // Sleep before next poll.
        sleep($sleep_interval);
    }
    // End of stream – send a comment to keep the connection tidy.
    echo ": keep‑alive\n\n";
    exit;
}
?>
