<?php
/**
 * OpenWA (@open-wa/wa-automate EASY API) integration.
 *
 * Outbound sendText, inbound webhooks, form notifications, admin status.
 *
 * @package BeyondInfinity
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST namespace for OpenWA routes (separate from ngt/v1 dashboard).
 *
 * @return string
 */
function bi_openwa_namespace() {
    return 'bi/v1';
}

/**
 * @return bool
 */
function bi_openwa_enabled() {
    return bi_theme_option_is_on( 'openwa_enabled' );
}

/**
 * @return array<string, mixed>
 */
function bi_openwa_config() {
    return [
        'enabled'         => bi_openwa_enabled(),
        'api_url'         => rtrim( (string) bi_get_theme_option( 'openwa_api_url', 'http://127.0.0.1:8080' ), '/' ),
        'api_key'         => (string) bi_get_theme_option( 'openwa_api_key', '' ),
        'session_id'      => sanitize_key( (string) bi_get_theme_option( 'openwa_session_id', '' ) ),
        'webhook_secret'  => (string) bi_get_theme_option( 'openwa_webhook_secret', '' ),
        'notify_forms'    => bi_theme_option_is_on( 'openwa_notify_forms' ),
        'auto_reply'      => bi_theme_option_is_on( 'openwa_auto_reply' ),
        'auto_reply_text' => (string) bi_get_theme_option(
            'openwa_auto_reply_text',
            'Thanks for messaging NextGen Tutors. We will reply during business hours.'
        ),
    ];
}

/**
 * Public webhook URL for wa-automate --webhook flag.
 *
 * @return string
 */
function bi_openwa_webhook_url() {
    $secret = bi_openwa_webhook_secret();
    $url    = rest_url( bi_openwa_namespace() . '/openwa/webhook' );
    if ( $secret ) {
        $url = add_query_arg( 'token', rawurlencode( $secret ), $url );
    }
    return $url;
}

/**
 * @return string
 */
function bi_openwa_webhook_secret() {
    return (string) bi_get_theme_option( 'openwa_webhook_secret', '' );
}

add_action( 'customize_save_after', 'bi_openwa_maybe_generate_webhook_secret' );
/**
 * Generate webhook secret when OpenWA is enabled but secret is empty.
 */
function bi_openwa_maybe_generate_webhook_secret() {
    if ( ! bi_theme_option_is_on( 'openwa_enabled' ) ) {
        return;
    }
    if ( bi_openwa_webhook_secret() ) {
        return;
    }
    set_theme_mod( 'openwa_webhook_secret', wp_generate_password( 32, false, false ) );
    bi_storage_set( 'options_loaded', false );
    bi_load_theme_options();
}

/**
 * Normalize phone or chat id to WhatsApp @c.us format.
 *
 * @param string $to Phone digits or chat id.
 * @return string
 */
function bi_openwa_chat_id( $to ) {
    $to = trim( (string) $to );
    if ( str_contains( $to, '@' ) ) {
        return $to;
    }
    $digits = preg_replace( '/[^0-9]/', '', $to );
    if ( ! $digits ) {
        return '';
    }
    return $digits . '@c.us';
}

/**
 * Build Easy API endpoint URL for a client method.
 *
 * @param string $method Client method e.g. sendText.
 * @return string
 */
function bi_openwa_api_endpoint( $method ) {
    $cfg  = bi_openwa_config();
    $base = $cfg['api_url'];
    if ( ! $base ) {
        return '';
    }
    if ( $cfg['session_id'] ) {
        return $base . '/' . rawurlencode( $cfg['session_id'] ) . '/' . $method;
    }
    return $base . '/' . $method;
}

/**
 * POST to Easy API middleware.
 *
 * @param string               $method Client method.
 * @param array<string, mixed> $args   Named args for the method.
 * @return array{ok: bool, data: mixed, error: string}
 */
function bi_openwa_request( $method, $args = [] ) {
    if ( ! bi_openwa_enabled() ) {
        return [
            'ok'    => false,
            'data'  => null,
            'error' => 'OpenWA disabled',
        ];
    }

    $cfg = bi_openwa_config();
    if ( ! $cfg['api_url'] ) {
        return [
            'ok'    => false,
            'data'  => null,
            'error' => 'Missing API URL',
        ];
    }

    $headers = [
        'Content-Type' => 'application/json',
    ];
    if ( $cfg['api_key'] ) {
        $headers['x-api-key'] = $cfg['api_key'];
    }

    $endpoint = bi_openwa_api_endpoint( $method );
    $body     = [
        'method' => $method,
        'args'   => $args,
    ];

    $response = wp_remote_post(
        $endpoint,
        [
            'timeout' => 20,
            'headers' => $headers,
            'body'    => wp_json_encode( $body ),
        ]
    );

    if ( is_wp_error( $response ) ) {
        return [
            'ok'    => false,
            'data'  => null,
            'error' => $response->get_error_message(),
        ];
    }

    $code = (int) wp_remote_retrieve_response_code( $response );
    $raw  = wp_remote_retrieve_body( $response );
    $data = json_decode( $raw, true );

    if ( $code >= 200 && $code < 300 ) {
        return [
            'ok'    => true,
            'data'  => $data,
            'error' => '',
        ];
    }

    $message = is_array( $data ) && isset( $data['message'] )
        ? (string) $data['message']
        : substr( $raw, 0, 200 );

    return [
        'ok'    => false,
        'data'  => $data,
        'error' => $message ?: sprintf( 'HTTP %d', $code ),
    ];
}

/**
 * Send a WhatsApp text message via Easy API.
 *
 * @param string $to      Phone or chat id.
 * @param string $content Message body.
 * @return array{ok: bool, data: mixed, error: string}
 */
function bi_openwa_send_text( $to, $content ) {
    $chat_id = bi_openwa_chat_id( $to );
    if ( ! $chat_id || ! trim( (string) $content ) ) {
        return [
            'ok'    => false,
            'data'  => null,
            'error' => 'Invalid recipient or message',
        ];
    }

    $result = bi_openwa_request(
        'sendText',
        [
            'to'      => $chat_id,
            'content' => (string) $content,
        ]
    );

    /**
     * Fires after an outbound OpenWA send attempt.
     *
     * @param array{ok: bool, data: mixed, error: string} $result
     * @param string                                      $chat_id
     * @param string                                      $content
     */
    do_action( 'bi_openwa_after_send', $result, $chat_id, $content );

    return $result;
}

/**
 * Ping Easy API connection state.
 *
 * @return array{ok: bool, state: string, error: string}
 */
function bi_openwa_connection_status() {
    if ( ! bi_openwa_enabled() ) {
        return [
            'ok'    => false,
            'state' => 'disabled',
            'error' => '',
        ];
    }

    $result = bi_openwa_request( 'getConnectionState', [] );
    if ( ! $result['ok'] ) {
        return [
            'ok'    => false,
            'state' => 'unreachable',
            'error' => $result['error'],
        ];
    }

    $state = '';
    if ( is_string( $result['data'] ) ) {
        $state = $result['data'];
    } elseif ( is_array( $result['data'] ) ) {
        $state = (string) ( $result['data']['state'] ?? $result['data']['connection'] ?? wp_json_encode( $result['data'] ) );
    }

    return [
        'ok'    => true,
        'state' => $state ?: 'connected',
        'error' => '',
    ];
}

/**
 * Append inbound message to rolling log.
 *
 * @param array<string, mixed> $entry Log entry.
 */
function bi_openwa_log_inbound( $entry ) {
    $log = get_option( 'bi_openwa_inbox', [] );
    if ( ! is_array( $log ) ) {
        $log = [];
    }
    $log[] = array_merge(
        [
            'received' => gmdate( 'c' ),
        ],
        $entry
    );
    update_option( 'bi_openwa_inbox', array_slice( $log, -50 ), false );
}

/**
 * Notify site WhatsApp number about a theme form submission.
 *
 * @param string               $form_id Form slug.
 * @param array<string, mixed> $payload Sanitized field map.
 */
function bi_openwa_notify_form_submission( $form_id, $payload ) {
    if ( ! bi_openwa_enabled() || ! bi_openwa_config()['notify_forms'] ) {
        return;
    }

    $lines = [
        sprintf( '[NextGen] New %s submission', $form_id ),
    ];
    foreach ( $payload as $key => $value ) {
        if ( is_array( $value ) ) {
            $value = implode( ', ', $value );
        }
        $lines[] = sprintf( '%s: %s', $key, $value );
    }

    $message = implode( "\n", $lines );
    $to      = apply_filters( 'bi_openwa_form_notify_recipient', bi_get_whatsapp(), $form_id, $payload );

    bi_openwa_send_text( $to, $message );
}

/**
 * Parse webhook body into a normalized inbound message array.
 *
 * @param array<string, mixed> $body Decoded JSON body.
 * @return array<string, mixed>|null
 */
function bi_openwa_parse_inbound( $body ) {
    if ( ! is_array( $body ) ) {
        return null;
    }

    if ( isset( $body['event'] ) && isset( $body['data'] ) && is_array( $body['data'] ) ) {
        $data = $body['data'];
        return [
            'event'   => (string) $body['event'],
            'from'    => (string) ( $data['from'] ?? $data['chatId'] ?? '' ),
            'body'    => (string) ( $data['body'] ?? $data['content'] ?? '' ),
            'id'      => (string) ( $data['messageId'] ?? $data['id'] ?? '' ),
            'type'    => (string) ( $data['type'] ?? 'text' ),
            'raw'     => $body,
        ];
    }

    if ( isset( $body['body'] ) && ( isset( $body['from'] ) || isset( $body['chatId'] ) ) ) {
        return [
            'event' => 'message',
            'from'  => (string) ( $body['from'] ?? $body['chatId'] ?? '' ),
            'body'  => (string) $body['body'],
            'id'    => (string) ( $body['id'] ?? '' ),
            'type'  => (string) ( $body['type'] ?? 'text' ),
            'raw'   => $body,
        ];
    }

    return null;
}

/**
 * @param WP_REST_Request $request Request.
 * @return bool|WP_Error
 */
function bi_openwa_webhook_permission( $request ) {
    $secret   = bi_openwa_webhook_secret();
    $provided = $request->get_header( 'x-bi-webhook-secret' );
    if ( ! $provided ) {
        $provided = $request->get_param( 'token' );
    }
    if ( ! $secret || ! $provided || ! hash_equals( $secret, (string) $provided ) ) {
        return new WP_Error( 'bi_openwa_forbidden', __( 'Invalid webhook secret.', 'beyondinfinity' ), [ 'status' => 403 ] );
    }
    return true;
}

/**
 * @param WP_REST_Request $request Request.
 * @return bool
 */
function bi_openwa_admin_permission( $request ) {
    return current_user_can( 'manage_options' );
}

add_action( 'rest_api_init', 'bi_openwa_register_rest_routes' );
function bi_openwa_register_rest_routes() {
    register_rest_route(
        bi_openwa_namespace(),
        '/openwa/webhook',
        [
            'methods'             => 'POST',
            'callback'            => 'bi_openwa_rest_webhook',
            'permission_callback' => 'bi_openwa_webhook_permission',
        ]
    );

    register_rest_route(
        bi_openwa_namespace(),
        '/openwa/status',
        [
            'methods'             => 'GET',
            'callback'            => 'bi_openwa_rest_status',
            'permission_callback' => 'bi_openwa_admin_permission',
        ]
    );

    register_rest_route(
        bi_openwa_namespace(),
        '/openwa/send',
        [
            'methods'             => 'POST',
            'callback'            => 'bi_openwa_rest_send',
            'permission_callback' => 'bi_openwa_admin_permission',
            'args'                => [
                'to'      => [
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'message' => [
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_textarea_field',
                ],
            ],
        ]
    );
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function bi_openwa_rest_webhook( $request ) {
    $body = $request->get_json_params();
    if ( ! is_array( $body ) ) {
        $body = [];
    }

    $parsed = bi_openwa_parse_inbound( $body );
    if ( $parsed ) {
        bi_openwa_log_inbound( $parsed );

        /**
         * Fires when OpenWA delivers an inbound message to WordPress.
         *
         * @param array<string, mixed> $parsed Normalized message.
         */
        do_action( 'bi_openwa_inbound_message', $parsed );

        if ( bi_openwa_config()['auto_reply'] && ! empty( $parsed['from'] ) && ! empty( $parsed['body'] ) ) {
            $reply = bi_openwa_config()['auto_reply_text'];
            if ( $reply ) {
                bi_openwa_send_text( $parsed['from'], $reply );
            }
        }
    } else {
        bi_openwa_log_inbound(
            [
                'event' => 'unknown',
                'raw'   => $body,
            ]
        );
    }

    return new WP_REST_Response( [ 'ok' => true ], 200 );
}

/**
 * @return WP_REST_Response
 */
function bi_openwa_rest_status() {
    $status = bi_openwa_connection_status();
    $log    = get_option( 'bi_openwa_inbox', [] );

    return new WP_REST_Response(
        [
            'enabled'     => bi_openwa_enabled(),
            'connection'  => $status,
            'webhook_url' => bi_openwa_webhook_url(),
            'recent'      => is_array( $log ) ? array_slice( array_reverse( $log ), 0, 5 ) : [],
        ],
        200
    );
}

/**
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function bi_openwa_rest_send( $request ) {
    $result = bi_openwa_send_text(
        $request->get_param( 'to' ),
        $request->get_param( 'message' )
    );
    if ( ! $result['ok'] ) {
        return new WP_Error( 'bi_openwa_send_failed', $result['error'], [ 'status' => 502 ] );
    }
    return new WP_REST_Response( $result, 200 );
}
