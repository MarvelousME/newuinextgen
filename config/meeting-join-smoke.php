<?php
/**
 * Meeting join smoke — run inside WP container.
 */
require '/var/www/html/wp-load.php';

$out = [
	'ngc_version' => defined( 'NGC_VERSION' ) ? NGC_VERSION : null,
	'meetings'    => class_exists( 'NGC_Meetings' ),
	'jitsi'       => class_exists( 'NGC_Jitsi_Meeting_Adapter' ),
];

$students = get_users( [ 'role__in' => [ 'student', 'ngt_student' ], 'number' => 1 ] );
$tutors   = get_users( [ 'role__in' => [ 'tutor', 'ngt_tutor' ], 'number' => 1 ] );
if ( ! $students || ! $tutors ) {
	$out['error'] = 'missing_roles';
	echo wp_json_encode( $out, JSON_UNESCAPED_SLASHES ) . PHP_EOL;
	exit( 1 );
}

$sid = (int) $students[0]->ID;
$tid = (int) $tutors[0]->ID;
$id  = NGC_Bookings::create(
	[
		'student_user_id'  => $sid,
		'tutor_user_id'    => $tid,
		'subject'          => 'Math A/V smoke',
		'scheduled_at'     => gmdate( 'Y-m-d H:i:s', time() + 3600 ),
		'duration_minutes' => 60,
		'amount'           => 1,
		'currency'         => 'ZAR',
		'notes'            => 'meeting_smoke',
		'meta'             => [ 'delivery' => 'online', 'smoke' => 1 ],
		'idempotency_key'  => 'meeting_smoke_' . gmdate( 'YmdHis' ),
	]
);

if ( is_wp_error( $id ) ) {
	$out['error'] = $id->get_error_message();
	echo wp_json_encode( $out, JSON_UNESCAPED_SLASHES ) . PHP_EOL;
	exit( 1 );
}

$tr  = NGC_Bookings::transition( (int) $id, 'confirmed', $sid );
$row = NGC_Bookings::format_session_row( NGC_Bookings::get( (int) $id ), $sid );
$meta = NGC_Bookings::get_meeting_meta( (int) $id );
$join = NGC_Meetings::join_url_for_user( (int) $id, $sid );

$out['booking_id'] = (int) $id;
$out['transition'] = is_wp_error( $tr ) ? $tr->get_error_message() : 'ok';
$out['joinUrl']    = $row['joinUrl'] ?? null;
$out['canJoin']    = $row['canJoin'] ?? null;
$out['provider']   = $row['meetingProvider'] ?? null;
$out['meta_room']  = $meta['room'] ?? null;
$out['join_api']   = is_wp_error( $join ) ? $join->get_error_message() : $join;
$out['host']       = wp_parse_url( (string) ( $row['joinUrl'] ?? '' ), PHP_URL_HOST );
$out['ok']         = ! empty( $row['canJoin'] ) && ! empty( $row['joinUrl'] ) && ( $out['host'] === 'meet.jit.si' || ! empty( $out['host'] ) );

echo wp_json_encode( $out, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
exit( $out['ok'] ? 0 : 2 );
