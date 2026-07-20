<?php
/**
 * Admin tools: sync launch pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function bi_find_page_by_slug( $slug ) {
    if ( ! $slug ) {
        return null;
    }
    $page = get_page_by_path( $slug, OBJECT, 'page' );
    if ( $page ) {
        return $page;
    }
    $posts = get_posts(
        [
            'name'           => $slug,
            'post_type'      => 'page',
            'post_status'    => [ 'publish', 'draft', 'private' ],
            'posts_per_page' => 1,
        ]
    );
    return $posts ? $posts[0] : null;
}

function bi_sync_launch_pages() {
    $pages = bi_load_page_map();
    if ( is_wp_error( $pages ) ) {
        return $pages;
    }

    $created    = 0;
    $updated    = 0;
    $front_id   = 0;

    foreach ( $pages as $page ) {
        $slug     = $page['slug'] ?? '';
        $existing = bi_find_page_by_slug( $slug );

        if ( $existing ) {
            $page_id = (int) $existing->ID;
            $updates = [
                'ID'          => $page_id,
                'post_status' => 'publish',
            ];
            if ( ! empty( $page['title'] ) && $page['title'] !== $existing->post_title ) {
                $updates['post_title'] = $page['title'];
            }
            if ( $slug && $slug !== $existing->post_name ) {
                $updates['post_name'] = $slug;
            }
            if ( 'publish' !== $existing->post_status
                || ( isset( $updates['post_title'] ) )
                || ( isset( $updates['post_name'] ) ) ) {
                wp_update_post( $updates );
            }
            ++$updated;
        } else {
            $page_id = wp_insert_post(
                [
                    'post_title'   => $page['title'],
                    'post_name'    => $slug,
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => '',
                ],
                true
            );
            if ( is_wp_error( $page_id ) ) {
                continue;
            }
            ++$created;
        }

        if ( is_wp_error( $page_id ) || ! $page_id ) {
            continue;
        }

        $template = $page['template'] ?? '';
        if ( ! empty( $page['is_front'] ) ) {
            $template = 'default';
            $front_id = (int) $page_id;
        }

        bi_bind_page_template( (int) $page_id, $template );

        if ( function_exists( 'bi_sync_page_prototype_content' ) ) {
            bi_sync_page_prototype_content( (int) $page_id, $slug );
        }

        if ( ! empty( $page['is_front'] ) ) {
            $front_id = (int) $page_id;
        }
    }

    if ( $front_id ) {
        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $front_id );
    }

    $privacy = bi_find_page_by_slug( 'privacy-policy' );
    if ( $privacy ) {
        update_option( 'wp_page_for_privacy_policy', (int) $privacy->ID );
    }

    flush_rewrite_rules( false );

    if ( function_exists( 'bi_sync_launch_nav' ) ) {
        bi_sync_launch_nav( true );
    }

    if ( function_exists( 'bi_ensure_global_header_style_default' ) ) {
        bi_ensure_global_header_style_default();
    }

    return compact( 'created', 'updated', 'front_id' );
}

/**
 * Bind the correct page template meta for a page.
 *
 * @param int    $page_id  Page ID.
 * @param string $template Template filename or "default".
 */
function bi_bind_page_template( $page_id, $template ) {
    if ( $page_id <= 0 ) {
        return;
    }
    $template = (string) $template;
    if ( $template && 'default' !== $template ) {
        update_post_meta( $page_id, '_wp_page_template', $template );
        return;
    }
    delete_post_meta( $page_id, '_wp_page_template' );
}

add_action( 'admin_menu', 'bi_admin_menu' );
function bi_admin_menu() {
    add_theme_page(
        __( 'Sync Launch Pages', 'beyondinfinity' ),
        __( 'Sync Launch Pages', 'beyondinfinity' ),
        'manage_options',
        'bi-sync-pages',
        'bi_sync_pages_screen'
    );
    add_theme_page(
        __( 'NextGen Operations', 'beyondinfinity' ),
        __( 'NextGen Operations', 'beyondinfinity' ),
        'manage_options',
        'bi-operations',
        'bi_operations_screen'
    );
}

function bi_sync_pages_screen() {
    if ( isset( $_POST['bi_sync_pages'] ) && check_admin_referer( 'bi_sync_pages' ) ) {
        $result = bi_sync_launch_pages();
        if ( is_wp_error( $result ) ) {
            echo '<div class="notice notice-error"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>' . esc_html(
                sprintf(
                    'Synced launch pages. Created: %d, updated: %d. Front page ID: %d.',
                    $result['created'],
                    $result['updated'],
                    $result['front_id'] ?? 0
                )
            ) . '</p></div>';
        }
    }
    $front = (int) get_option( 'page_on_front' );
    $sc    = function_exists( 'bi_ngc_shortcode_health' ) ? bi_ngc_shortcode_health() : [ 'ok' => false, 'missing' => [] ];
    $companion = class_exists( 'NGC_Plugin', false );
    ?>
    <div class="wrap">
      <h1><?php esc_html_e( 'Sync BeyondInfinity Launch Pages', 'beyondinfinity' ); ?></h1>
      <p><?php esc_html_e( 'Creates or updates all launch pages from content/page-map.json without re-activating the theme.', 'beyondinfinity' ); ?></p>
      <p><?php printf( esc_html__( 'Current front page ID: %d (%s)', 'beyondinfinity' ), $front, $front ? esc_html( get_the_title( $front ) ) : esc_html__( 'not set', 'beyondinfinity' ) ); ?></p>

      <h2><?php esc_html_e( 'Shortcode status', 'beyondinfinity' ); ?></h2>
      <?php if ( $sc['ok'] ) : ?>
        <p class="notice notice-success inline" style="padding:8px 12px;display:inline-block"><?php esc_html_e( 'All 11 ngc_* shortcodes are registered.', 'beyondinfinity' ); ?></p>
        <p><small><?php echo $companion ? esc_html__( 'Source: nextgencompanion plugin', 'beyondinfinity' ) : esc_html__( 'Source: theme fallbacks (activate nextgencompanion for full data layer)', 'beyondinfinity' ); ?></small></p>
      <?php else : ?>
        <div class="notice notice-error"><p><strong><?php esc_html_e( 'Missing shortcodes:', 'beyondinfinity' ); ?></strong> <?php echo esc_html( implode( ', ', $sc['missing'] ) ); ?></p></div>
      <?php endif; ?>

      <h2><?php esc_html_e( 'Page touchpoint audit', 'beyondinfinity' ); ?></h2>
      <p><?php esc_html_e( 'Filesystem and registry checks for all launch pages (23 in page-map.json).', 'beyondinfinity' ); ?></p>
      <?php
      $audit = function_exists( 'bi_pages_touchpoint_audit' ) ? bi_pages_touchpoint_audit() : [];
      $all_ok = true;
      ?>
      <table class="widefat striped" style="max-width:960px">
        <thead>
          <tr>
            <th><?php esc_html_e( 'Slug', 'beyondinfinity' ); ?></th>
            <th><?php esc_html_e( 'Source', 'beyondinfinity' ); ?></th>
            <th><?php esc_html_e( 'Map', 'beyondinfinity' ); ?></th>
            <th><?php esc_html_e( 'Template', 'beyondinfinity' ); ?></th>
            <th><?php esc_html_e( 'Default', 'beyondinfinity' ); ?></th>
            <th><?php esc_html_e( 'SEO', 'beyondinfinity' ); ?></th>
            <th><?php esc_html_e( 'Config', 'beyondinfinity' ); ?></th>
            <th><?php esc_html_e( 'Shortcodes', 'beyondinfinity' ); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ( $audit as $row ) :
              $row_ok = $row['in_page_map'] && $row['template_ok'] && $row['default_ok'] && ( null === $row['shortcodes_ok'] || $row['shortcodes_ok'] );
              if ( ! $row_ok ) {
                  $all_ok = false;
              }
              ?>
            <tr>
              <td><code><?php echo esc_html( $row['slug'] ); ?></code></td>
              <td><?php echo esc_html( $row['source'] ); ?></td>
              <td><?php echo $row['in_page_map'] ? '✓' : '✗'; ?></td>
              <td><?php echo $row['template_ok'] ? '✓' : '✗'; ?></td>
              <td><?php echo $row['default_ok'] ? '✓' : '✗'; ?></td>
              <td><?php echo $row['seo_meta'] ? '✓' : '✗'; ?></td>
              <td><?php echo ! empty( $row['config_defaults'] ) ? esc_html( implode( ', ', $row['config_defaults_keys'] ?? [] ) ) : '—'; ?></td>
              <td>
                <?php
                if ( null === $row['shortcodes_ok'] ) {
                    echo '—';
                } elseif ( $row['shortcodes_ok'] ) {
                    echo '✓';
                } else {
                    echo '✗ ' . esc_html( implode( ', ', $row['shortcodes_miss'] ) );
                }
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php if ( $all_ok ) : ?>
        <p class="notice notice-success inline" style="padding:8px 12px;display:inline-block;margin-top:12px"><?php esc_html_e( 'All page touchpoints pass filesystem checks.', 'beyondinfinity' ); ?></p>
      <?php else : ?>
        <p class="notice notice-warning inline" style="padding:8px 12px;display:inline-block;margin-top:12px"><?php esc_html_e( 'Some touchpoints need attention — see PAGES-AUDIT-REPORT.md.', 'beyondinfinity' ); ?></p>
      <?php endif; ?>

      <?php
      $drift = function_exists( 'bi_page_map_registry_drift' ) ? bi_page_map_registry_drift() : [];
      if ( ! empty( $drift ) ) :
          ?>
        <h2><?php esc_html_e( 'Registry drift', 'beyondinfinity' ); ?></h2>
        <div class="notice notice-warning"><ul style="margin:8px 0;padding-left:20px">
          <?php foreach ( $drift as $msg ) : ?>
            <li><?php echo esc_html( $msg ); ?></li>
          <?php endforeach; ?>
        </ul></div>
      <?php elseif ( function_exists( 'bi_page_map_registry_drift' ) ) : ?>
        <p class="notice notice-success inline" style="padding:8px 12px;display:inline-block;margin-top:12px"><?php esc_html_e( 'page-map.json and pages registry are in sync.', 'beyondinfinity' ); ?></p>
      <?php endif; ?>

      <h2><?php esc_html_e( 'OpenWA integration', 'beyondinfinity' ); ?></h2>
      <?php
      $openwa_on = function_exists( 'bi_openwa_enabled' ) && bi_openwa_enabled();
      $openwa    = function_exists( 'bi_openwa_connection_status' ) ? bi_openwa_connection_status() : [ 'ok' => false, 'state' => 'missing', 'error' => '' ];
      ?>
      <?php if ( ! function_exists( 'bi_openwa_enabled' ) ) : ?>
        <p><?php esc_html_e( 'OpenWA module not loaded.', 'beyondinfinity' ); ?></p>
      <?php elseif ( ! $openwa_on ) : ?>
        <p class="notice notice-info inline" style="padding:8px 12px;display:inline-block"><?php esc_html_e( 'OpenWA is disabled. Enable it under Appearance → Customize → Integrations → OpenWA.', 'beyondinfinity' ); ?></p>
      <?php else : ?>
        <p>
          <?php
          printf(
            esc_html__( 'Connection: %1$s%2$s', 'beyondinfinity' ),
            $openwa['ok'] ? '✓ ' : '✗ ',
            esc_html( $openwa['state'] . ( $openwa['error'] ? ' — ' . $openwa['error'] : '' ) )
          );
          ?>
        </p>
        <p><strong><?php esc_html_e( 'Webhook URL (for wa-automate --webhook):', 'beyondinfinity' ); ?></strong><br>
          <code style="word-break:break-all"><?php echo esc_html( bi_openwa_webhook_url() ); ?></code></p>
        <?php
        $inbox = get_option( 'bi_openwa_inbox', [] );
        if ( is_array( $inbox ) && ! empty( $inbox ) ) :
            $recent = array_slice( array_reverse( $inbox ), 0, 3 );
            ?>
          <p><strong><?php esc_html_e( 'Recent inbound (last 3):', 'beyondinfinity' ); ?></strong></p>
          <ul style="list-style:disc;margin-left:20px">
            <?php foreach ( $recent as $msg ) : ?>
              <li>
                <?php
                echo esc_html(
                    sprintf(
                        '%s — %s: %s',
                        $msg['received'] ?? '',
                        $msg['from'] ?? ( $msg['event'] ?? 'event' ),
                        isset( $msg['body'] ) ? wp_trim_words( (string) $msg['body'], 12, '…' ) : '(no body)'
                    )
                );
                ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      <?php endif; ?>

      <form method="post">
        <?php wp_nonce_field( 'bi_sync_pages' ); ?>
        <button type="submit" name="bi_sync_pages" class="button button-primary"><?php esc_html_e( 'Sync Pages Now', 'beyondinfinity' ); ?></button>
      </form>
    </div>
    <?php
}

add_action( 'admin_post_bi_approve_tutor', 'bi_admin_post_approve_tutor' );
/**
 * Admin POST handler — approve tutor and run workflow pack.
 */
function bi_admin_post_approve_tutor() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Forbidden', 'beyondinfinity' ), 403 );
    }
    check_admin_referer( 'bi_approve_tutor' );

    $user_id = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
    $result  = function_exists( 'bi_workflow_emit_tutor_approved' )
        ? bi_workflow_emit_tutor_approved( $user_id )
        : new WP_Error( 'bi_workflow_missing', __( 'Workflow module unavailable.', 'beyondinfinity' ) );

    $redirect = wp_get_referer() ?: admin_url( 'themes.php?page=bi-operations' );
    if ( is_wp_error( $result ) ) {
        wp_safe_redirect( add_query_arg( 'bi_tutor_error', rawurlencode( $result->get_error_message() ), $redirect ) );
        exit;
    }
    wp_safe_redirect( add_query_arg( 'bi_tutor_approved', (string) $user_id, $redirect ) );
    exit;
}

/**
 * Operations queue screen — form queue, tutor approvals, workflow log peek.
 */
function bi_operations_screen() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_GET['bi_tutor_approved'] ) ) {
        $uid = (int) $_GET['bi_tutor_approved'];
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(
            sprintf( __( 'Tutor approval workflow dispatched for user ID %d.', 'beyondinfinity' ), $uid )
        ) . '</p></div>';
    }
    if ( ! empty( $_GET['bi_tutor_error'] ) ) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( wp_unslash( $_GET['bi_tutor_error'] ) ) . '</p></div>';
    }

    $form_queue = get_option( 'ngc_form_queue', [] );
    if ( ! is_array( $form_queue ) ) {
        $form_queue = [];
    }
    $form_queue = array_reverse( array_slice( $form_queue, -20 ) );

    $workflow_log = get_option( 'bi_workflow_log', [] );
    if ( ! is_array( $workflow_log ) ) {
        $workflow_log = [];
    }
    $workflow_log = array_reverse( array_slice( $workflow_log, -15 ) );

    $rtm_queue = get_option( 'bi_rtm_queue', [] );
    if ( ! is_array( $rtm_queue ) ) {
        $rtm_queue = [];
    }
    $rtm_queue = array_reverse( array_slice( $rtm_queue, -10 ) );

    $tutor_apps = array_values(
        array_filter(
            $form_queue,
            static function ( $entry ) {
                return is_array( $entry ) && ( $entry['form'] ?? '' ) === 'become_tutor';
            }
        )
    );
    ?>
    <div class="wrap">
      <h1><?php esc_html_e( 'NextGen Operations', 'beyondinfinity' ); ?></h1>
      <p><?php esc_html_e( 'Review queued intakes, approve tutors when a matching WordPress user exists, and inspect recent workflow activity.', 'beyondinfinity' ); ?></p>

      <h2><?php esc_html_e( 'Tutor applications (queued)', 'beyondinfinity' ); ?></h2>
      <?php if ( empty( $tutor_apps ) ) : ?>
        <p><?php esc_html_e( 'No tutor applications in the theme form queue.', 'beyondinfinity' ); ?></p>
      <?php else : ?>
        <table class="widefat striped" style="max-width:960px">
          <thead>
            <tr>
              <th><?php esc_html_e( 'Submitted', 'beyondinfinity' ); ?></th>
              <th><?php esc_html_e( 'Applicant', 'beyondinfinity' ); ?></th>
              <th><?php esc_html_e( 'Email', 'beyondinfinity' ); ?></th>
              <th><?php esc_html_e( 'WP user', 'beyondinfinity' ); ?></th>
              <th><?php esc_html_e( 'Action', 'beyondinfinity' ); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ( $tutor_apps as $entry ) :
                $data  = is_array( $entry['data'] ?? null ) ? $entry['data'] : [];
                $email = sanitize_email( (string) ( $data['email'] ?? '' ) );
                $name  = (string) ( $data['name'] ?? $data['full_name'] ?? '' );
                $user  = $email ? get_user_by( 'email', $email ) : false;
                ?>
              <tr>
                <td><?php echo esc_html( (string) ( $entry['created'] ?? '' ) ); ?></td>
                <td><?php echo esc_html( $name ); ?></td>
                <td><?php echo esc_html( $email ); ?></td>
                <td>
                  <?php
                  if ( $user ) {
                      echo esc_html( sprintf( '#%d %s', $user->ID, $user->user_login ) );
                  } else {
                      esc_html_e( 'No matching user — create account in companion/WP first', 'beyondinfinity' );
                  }
                  ?>
                </td>
                <td>
                  <?php if ( $user && function_exists( 'bi_workflow_emit_tutor_approved' ) ) : ?>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                      <?php wp_nonce_field( 'bi_approve_tutor' ); ?>
                      <input type="hidden" name="action" value="bi_approve_tutor" />
                      <input type="hidden" name="user_id" value="<?php echo esc_attr( (string) $user->ID ); ?>" />
                      <button type="submit" class="button button-secondary"><?php esc_html_e( 'Approve tutor', 'beyondinfinity' ); ?></button>
                    </form>
                  <?php else : ?>
                    —
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <h2><?php esc_html_e( 'Recent workflow log', 'beyondinfinity' ); ?></h2>
      <?php if ( empty( $workflow_log ) ) : ?>
        <p><?php esc_html_e( 'No workflow events logged yet.', 'beyondinfinity' ); ?></p>
      <?php else : ?>
        <table class="widefat striped" style="max-width:960px">
          <thead>
            <tr>
              <th><?php esc_html_e( 'Time', 'beyondinfinity' ); ?></th>
              <th><?php esc_html_e( 'Source', 'beyondinfinity' ); ?></th>
              <th><?php esc_html_e( 'Context', 'beyondinfinity' ); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ( $workflow_log as $row ) : ?>
              <tr>
                <td><?php echo esc_html( (string) ( $row['created'] ?? '' ) ); ?></td>
                <td><code><?php echo esc_html( (string) ( $row['source'] ?? '' ) ); ?></code></td>
                <td><code><?php echo esc_html( wp_json_encode( $row['context'] ?? [] ) ); ?></code></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <h2><?php esc_html_e( 'Recent RTM queue', 'beyondinfinity' ); ?></h2>
      <?php if ( empty( $rtm_queue ) ) : ?>
        <p><?php esc_html_e( 'No staff room messages queued.', 'beyondinfinity' ); ?></p>
      <?php else : ?>
        <table class="widefat striped" style="max-width:960px">
          <thead>
            <tr>
              <th><?php esc_html_e( 'Time', 'beyondinfinity' ); ?></th>
              <th><?php esc_html_e( 'Room', 'beyondinfinity' ); ?></th>
              <th><?php esc_html_e( 'Message', 'beyondinfinity' ); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ( $rtm_queue as $row ) : ?>
              <tr>
                <td><?php echo esc_html( (string) ( $row['created'] ?? '' ) ); ?></td>
                <td><code><?php echo esc_html( (string) ( $row['room'] ?? '' ) ); ?></code></td>
                <td><?php echo esc_html( (string) ( $row['message'] ?? '' ) ); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>

      <h2><?php esc_html_e( 'All recent form queue', 'beyondinfinity' ); ?></h2>
      <?php if ( empty( $form_queue ) ) : ?>
        <p><?php esc_html_e( 'No form submissions queued.', 'beyondinfinity' ); ?></p>
      <?php else : ?>
        <table class="widefat striped" style="max-width:960px">
          <thead>
            <tr>
              <th><?php esc_html_e( 'Time', 'beyondinfinity' ); ?></th>
              <th><?php esc_html_e( 'Form', 'beyondinfinity' ); ?></th>
              <th><?php esc_html_e( 'Payload', 'beyondinfinity' ); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ( $form_queue as $entry ) : ?>
              <tr>
                <td><?php echo esc_html( (string) ( $entry['created'] ?? '' ) ); ?></td>
                <td><code><?php echo esc_html( (string) ( $entry['form'] ?? '' ) ); ?></code></td>
                <td><code><?php echo esc_html( wp_json_encode( $entry['data'] ?? [] ) ); ?></code></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
    <?php
}

add_action( 'after_switch_theme', 'bi_create_launch_pages' );
function bi_create_launch_pages() {
    bi_sync_launch_pages();
}

/**
 * Repair front page assignment if the Home page exists but is not set as front.
 */
add_action( 'init', 'bi_repair_front_page_once', 20 );
function bi_repair_front_page_once() {
    if ( get_option( 'bi_front_page_repaired' ) ) {
        return;
    }

    $front_id = (int) get_option( 'page_on_front' );
    $home     = bi_find_page_by_slug( 'home' );

    if ( $home ) {
        if ( 'page' !== get_option( 'show_on_front' ) || $front_id !== (int) $home->ID ) {
            update_option( 'show_on_front', 'page' );
            update_option( 'page_on_front', $home->ID );
            delete_post_meta( $home->ID, '_wp_page_template' );
        }
        update_option( 'bi_front_page_repaired', 1 );
        return;
    }

    if ( is_admin() && current_user_can( 'manage_options' ) ) {
        bi_sync_launch_pages();
        update_option( 'bi_front_page_repaired', 1 );
    }
}
