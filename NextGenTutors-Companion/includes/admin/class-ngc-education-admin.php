<?php
/**
 * Education admin screens — live WP / booking / review data (not placeholders).
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Students / parents / subjects / lessons live data views.
 */
final class NGC_Education_Admin {

	/** @var int */
	const PER_PAGE = 20;

	/**
	 * Init — catalog callbacks only; menus come from NGC_Admin_Shell.
	 */
	public static function init() {
		// No separate menus; catalog routes to these renderers.
	}

	/**
	 * Students hub.
	 */
	public static function render_students() {
		self::render_role_directory(
			__( 'Students', 'nextgencompanion' ),
			__( 'Registered student accounts from WordPress roles (live query).', 'nextgencompanion' ),
			[ 'student', 'ngc_student', 'subscriber' ],
			'students'
		);
	}

	/**
	 * Student directory alias.
	 */
	public static function render_student_directory() {
		self::render_students();
	}

	/**
	 * Attendance — bookings-linked presence when available.
	 */
	public static function render_attendance() {
		$rows = self::fetch_booking_rows( 40 );
		ob_start();
		if ( ! $rows ) {
			echo '<div class="ngt-admin-card"><p>' . esc_html__( 'No recent booking attendance signals yet. Attendance is derived from booking records when present.', 'nextgencompanion' ) . '</p>';
			echo '<p><a class="button" href="' . esc_url( admin_url( 'admin.php?page=ngt-edu-lessons' ) ) . '">' . esc_html__( 'Open Lessons', 'nextgencompanion' ) . '</a></p></div>';
		} else {
			echo '<table class="widefat striped"><thead><tr>';
			echo '<th>' . esc_html__( 'Booking', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'Subject', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'Status', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'When', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'Student', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'Tutor', 'nextgencompanion' ) . '</th>';
			echo '</tr></thead><tbody>';
			foreach ( $rows as $row ) {
				$id      = (string) ( $row['id'] ?? $row['booking_id'] ?? '' );
				$subject = (string) ( $row['subject'] ?? '' );
				$status  = (string) ( $row['status'] ?? '' );
				$when    = (string) ( $row['starts_at'] ?? $row['scheduled_at'] ?? $row['created_at'] ?? '' );
				$student = self::user_label( (int) ( $row['student_user_id'] ?? 0 ) );
				$tutor   = self::user_label( (int) ( $row['tutor_user_id'] ?? 0 ) );
				echo '<tr>';
				echo '<td>' . esc_html( $id ) . '</td>';
				echo '<td>' . esc_html( $subject ) . '</td>';
				echo '<td>' . esc_html( $status ) . '</td>';
				echo '<td>' . esc_html( $when ) . '</td>';
				echo '<td>' . esc_html( $student ) . '</td>';
				echo '<td>' . esc_html( $tutor ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}
		self::page( __( 'Attendance', 'nextgencompanion' ), __( 'Presence derived from booking activity.', 'nextgencompanion' ), ob_get_clean() );
	}

	/**
	 * Assessments — recent NGC_Reviews when available.
	 */
	public static function render_assessments() {
		$rows  = [];
		$count = 0;
		if ( class_exists( 'NGC_Reviews' ) && method_exists( 'NGC_Reviews', 'recent' ) ) {
			$rows = (array) NGC_Reviews::recent( 25 );
		}
		if ( class_exists( 'NGC_Reviews' ) && method_exists( 'NGC_Reviews', 'count' ) ) {
			$count = (int) NGC_Reviews::count();
		} else {
			$count = count( $rows );
		}

		$lms_note = '';
		if ( defined( 'STM_LMS_VERSION' ) || class_exists( 'STM_LMS_Course' ) || class_exists( 'NGC_Masterstudy_Adapter' ) ) {
			$lms_note = __( 'MasterStudy LMS detected — gradebook bridge remains optional; reviews below are platform assessment signals.', 'nextgencompanion' );
		} else {
			$lms_note = __( 'Full LMS gradebook integration lands with MasterStudy when configured.', 'nextgencompanion' );
		}

		ob_start();
		echo '<div class="ngt-admin-card"><p>' . esc_html(
			sprintf(
				/* translators: %d: review count */
				__( 'Assessment and review records available: %d.', 'nextgencompanion' ),
				$count
			)
		) . ' ' . esc_html( $lms_note ) . '</p></div>';

		if ( $rows ) {
			echo '<table class="widefat striped"><thead><tr>';
			echo '<th>' . esc_html__( 'ID', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'Tutor', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'Parent', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'Rating', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'Status', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'When', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'Comment', 'nextgencompanion' ) . '</th>';
			echo '</tr></thead><tbody>';
			foreach ( $rows as $row ) {
				$comment = (string) ( $row['comment'] ?? '' );
				if ( strlen( $comment ) > 80 ) {
					$comment = substr( $comment, 0, 77 ) . '…';
				}
				echo '<tr>';
				echo '<td>' . esc_html( (string) ( $row['id'] ?? '' ) ) . '</td>';
				echo '<td>' . esc_html( self::user_label( (int) ( $row['tutor_user_id'] ?? 0 ) ) ) . '</td>';
				echo '<td>' . esc_html( self::user_label( (int) ( $row['parent_user_id'] ?? 0 ) ) ) . '</td>';
				echo '<td>' . esc_html( (string) ( $row['rating'] ?? '' ) ) . '</td>';
				echo '<td>' . esc_html( (string) ( $row['status'] ?? '' ) ) . '</td>';
				echo '<td>' . esc_html( (string) ( $row['created_at'] ?? '' ) ) . '</td>';
				echo '<td>' . esc_html( $comment ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		} else {
			echo '<div class="ngt-admin-card"><p>' . esc_html__( 'No reviews yet. Parent ratings after completed bookings will appear here.', 'nextgencompanion' ) . '</p></div>';
		}
		self::page( __( 'Assessments', 'nextgencompanion' ), __( 'Learning assessment signals from platform reviews/LMS.', 'nextgencompanion' ), ob_get_clean() );
	}

	/**
	 * Certificates — actionable empty state + MasterStudy / LMS detection.
	 */
	public static function render_certificates() {
		$ms_active = defined( 'STM_LMS_VERSION' ) || class_exists( 'STM_LMS_Course' );
		$ms_adapter = class_exists( 'NGC_Masterstudy_Adapter' );
		$cert_class = class_exists( 'STM_LMS_Certificates' ) || class_exists( 'STM_Certificates' ) || class_exists( 'STM_LMS_Certificate' );

		ob_start();
		echo '<div class="ngt-admin-card">';
		if ( $ms_active || $cert_class ) {
			echo '<p>' . esc_html__( 'MasterStudy LMS is active. Issued certificates will list here when the LMS certificate module stores records on this site.', 'nextgencompanion' ) . '</p>';
			if ( $cert_class ) {
				echo '<p><em>' . esc_html__( 'Certificate class detected — waiting for issued certificate data.', 'nextgencompanion' ) . '</em></p>';
			}
		} elseif ( $ms_adapter ) {
			echo '<p>' . esc_html__( 'NextGen MasterStudy adapter is available, but MasterStudy LMS is not active on this site.', 'nextgencompanion' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'No certificate issuer configured. When LMS certificates are enabled, issued certificates will list here.', 'nextgencompanion' ) . '</p>';
		}
		echo '<p>';
		echo '<a class="button button-primary" href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html__( 'Manage plugins', 'nextgencompanion' ) . '</a> ';
		if ( current_user_can( 'manage_options' ) ) {
			echo '<a class="button" href="' . esc_url( admin_url( 'admin.php?page=ngc-workflow-masterstudy' ) ) . '">' . esc_html__( 'MasterStudy status', 'nextgencompanion' ) . '</a>';
		}
		echo '</p></div>';
		self::page( __( 'Certificates', 'nextgencompanion' ), __( 'Issued learning certificates.', 'nextgencompanion' ), ob_get_clean() );
	}

	/**
	 * Parents directory.
	 */
	public static function render_parents() {
		self::render_role_directory(
			__( 'Parents', 'nextgencompanion' ),
			__( 'Parent accounts from WordPress roles (live query).', 'nextgencompanion' ),
			[ 'parent', 'ngc_parent' ],
			'parents'
		);
	}

	/**
	 * Lessons from bookings / calendar.
	 */
	public static function render_lessons() {
		$rows = self::fetch_booking_rows( 40 );
		ob_start();
		if ( ! $rows ) {
			echo '<div class="ngt-admin-card"><p>' . esc_html__( 'No lessons found yet. Lessons are listed from booking records (scheduled sessions).', 'nextgencompanion' ) . '</p></div>';
		} else {
			echo '<table class="widefat striped"><thead><tr>';
			echo '<th>' . esc_html__( 'ID', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'Subject', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'Scheduled', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'Duration', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'Status', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'Student', 'nextgencompanion' ) . '</th>';
			echo '<th>' . esc_html__( 'Tutor', 'nextgencompanion' ) . '</th>';
			echo '</tr></thead><tbody>';
			foreach ( $rows as $row ) {
				$dur = (int) ( $row['duration_minutes'] ?? 0 );
				echo '<tr>';
				echo '<td>' . esc_html( (string) ( $row['id'] ?? $row['booking_id'] ?? '' ) ) . '</td>';
				echo '<td>' . esc_html( (string) ( $row['subject'] ?? '' ) ) . '</td>';
				echo '<td>' . esc_html( (string) ( $row['starts_at'] ?? $row['scheduled_at'] ?? '' ) ) . '</td>';
				echo '<td>' . esc_html( $dur ? $dur . ' min' : '—' ) . '</td>';
				echo '<td>' . esc_html( (string) ( $row['status'] ?? '' ) ) . '</td>';
				echo '<td>' . esc_html( self::user_label( (int) ( $row['student_user_id'] ?? 0 ) ) ) . '</td>';
				echo '<td>' . esc_html( self::user_label( (int) ( $row['tutor_user_id'] ?? 0 ) ) ) . '</td>';
				echo '</tr>';
			}
			echo '</tbody></table>';
		}
		self::page( __( 'Lessons', 'nextgencompanion' ), __( 'Scheduled sessions from live booking data.', 'nextgencompanion' ), ob_get_clean() );
	}

	/**
	 * Subjects from tutor meta / options.
	 */
	public static function render_subjects() {
		$subjects = [];
		if ( function_exists( 'get_terms' ) ) {
			$terms = get_terms(
				[
					'taxonomy'   => 'tutor_subject',
					'hide_empty' => false,
				]
			);
			if ( ! is_wp_error( $terms ) && $terms ) {
				foreach ( $terms as $term ) {
					$subjects[] = [ 'name' => $term->name, 'count' => (int) $term->count ];
				}
			}
		}
		if ( ! $subjects && class_exists( 'NGC_Marketplace' ) && method_exists( 'NGC_Marketplace', 'list_subjects' ) ) {
			foreach ( (array) NGC_Marketplace::list_subjects() as $s ) {
				$subjects[] = is_array( $s ) ? $s : [ 'name' => (string) $s, 'count' => 0 ];
			}
		}
		ob_start();
		if ( ! $subjects ) {
			echo '<div class="ngt-admin-card"><p>' . esc_html__( 'No subject taxonomy terms found yet. Subjects appear as tutors publish profiles.', 'nextgencompanion' ) . '</p></div>';
		} else {
			echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Subject', 'nextgencompanion' ) . '</th><th>' . esc_html__( 'Count', 'nextgencompanion' ) . '</th></tr></thead><tbody>';
			foreach ( $subjects as $s ) {
				echo '<tr><td>' . esc_html( (string) ( $s['name'] ?? '' ) ) . '</td><td>' . esc_html( (string) ( $s['count'] ?? 0 ) ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}
		self::page( __( 'Subjects', 'nextgencompanion' ), __( 'Teaching subjects from live taxonomy / marketplace data.', 'nextgencompanion' ), ob_get_clean() );
	}

	/**
	 * @param string   $title   Title.
	 * @param string   $summary Summary.
	 * @param string[] $roles   Roles.
	 * @param string   $context Context key.
	 */
	private static function render_role_directory( $title, $summary, array $roles, $context ) {
		$paged    = max( 1, (int) ( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = self::PER_PAGE;
		$offset   = ( $paged - 1 ) * $per_page;
		$page_slug = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$query_args = [
			'role__in' => $roles,
			'number'   => $per_page,
			'offset'   => $offset,
			'orderby'  => 'registered',
			'order'    => 'DESC',
			'count_total' => true,
		];
		if ( $search ) {
			$query_args['search']         = '*' . $search . '*';
			$query_args['search_columns'] = [ 'user_login', 'user_email', 'display_name', 'user_nicename' ];
		}

		$user_query = new WP_User_Query( $query_args );
		$users      = $user_query->get_results();
		$total      = (int) $user_query->get_total();
		$total_pages = max( 1, (int) ceil( $total / $per_page ) );

		ob_start();
		?>
		<div class="ngt-admin-card" style="margin-bottom:12px">
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: context label, 2: comma-separated role slugs, 3: total users */
						__( 'Showing %1$s matching roles %2$s — %3$d account(s).', 'nextgencompanion' ),
						$context,
						implode( ', ', $roles ),
						$total
					)
				);
				?>
			</p>
			<p class="description"><?php echo esc_html( sprintf( /* translators: %s: role list */ __( 'Role filter: %s', 'nextgencompanion' ), implode( ', ', $roles ) ) ); ?></p>
		</div>
		<form method="get" style="margin-bottom:12px">
			<input type="hidden" name="page" value="<?php echo esc_attr( $page_slug ); ?>" />
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search name or email', 'nextgencompanion' ); ?>" />
			<?php submit_button( __( 'Search', 'nextgencompanion' ), 'secondary', '', false ); ?>
			<?php if ( $search ) : ?>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page_slug ) ); ?>"><?php esc_html_e( 'Clear', 'nextgencompanion' ); ?></a>
			<?php endif; ?>
		</form>
		<?php if ( ! $users ) : ?>
			<div class="ngt-admin-card">
				<p><?php esc_html_e( 'No matching accounts for these roles.', 'nextgencompanion' ); ?></p>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>"><?php esc_html_e( 'Open WordPress Users', 'nextgencompanion' ); ?></a>
					<a class="button" href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>"><?php esc_html_e( 'Add user', 'nextgencompanion' ); ?></a>
				</p>
			</div>
		<?php else : ?>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Name', 'nextgencompanion' ); ?></th>
					<th><?php esc_html_e( 'Email', 'nextgencompanion' ); ?></th>
					<th><?php esc_html_e( 'Roles', 'nextgencompanion' ); ?></th>
					<th><?php esc_html_e( 'Registered', 'nextgencompanion' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $users as $user ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>"><?php echo esc_html( $user->display_name ); ?></a>
						</td>
						<td><?php echo esc_html( $user->user_email ); ?></td>
						<td><?php echo esc_html( implode( ', ', $user->roles ) ); ?></td>
						<td><?php echo esc_html( $user->user_registered ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav bottom" style="margin-top:12px">
					<div class="tablenav-pages">
						<span class="displaying-num">
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: page, 2: total pages */
									__( 'Page %1$d of %2$d', 'nextgencompanion' ),
									$paged,
									$total_pages
								)
							);
							?>
						</span>
						<?php
						$base_args = [ 'page' => $page_slug ];
						if ( $search ) {
							$base_args['s'] = $search;
						}
						if ( $paged > 1 ) {
							$prev = add_query_arg( array_merge( $base_args, [ 'paged' => $paged - 1 ] ), admin_url( 'admin.php' ) );
							echo '<a class="button" href="' . esc_url( $prev ) . '">' . esc_html__( 'Previous', 'nextgencompanion' ) . '</a> ';
						}
						if ( $paged < $total_pages ) {
							$next = add_query_arg( array_merge( $base_args, [ 'paged' => $paged + 1 ] ), admin_url( 'admin.php' ) );
							echo '<a class="button" href="' . esc_url( $next ) . '">' . esc_html__( 'Next', 'nextgencompanion' ) . '</a>';
						}
						?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
		<?php
		self::page( $title, $summary, ob_get_clean() );
	}

	/**
	 * Fetch booking rows via NGC_Bookings helpers or safe $wpdb fallback.
	 *
	 * @param int $limit Max rows.
	 * @return array<int, array<string, mixed>>
	 */
	private static function fetch_booking_rows( $limit = 25 ) {
		$limit = max( 1, (int) $limit );
		$rows  = [];

		if ( class_exists( 'NGC_Bookings' ) && method_exists( 'NGC_Bookings', 'recent' ) ) {
			$rows = (array) NGC_Bookings::recent( $limit );
		} elseif ( class_exists( 'NGC_Bookings' ) && method_exists( 'NGC_Bookings', 'list' ) ) {
			$rows = (array) NGC_Bookings::list( [ 'limit' => $limit ] );
		} elseif ( class_exists( 'NGC_Bookings' ) && method_exists( 'NGC_Bookings', 'query' ) ) {
			foreach ( (array) NGC_Bookings::query( [ 'limit' => $limit ] ) as $row ) {
				$rows[] = [
					'id'               => (int) ( $row->id ?? 0 ),
					'booking_id'       => (int) ( $row->id ?? 0 ),
					'status'           => (string) ( $row->status ?? '' ),
					'subject'          => (string) ( $row->subject ?? '' ),
					'starts_at'        => (string) ( $row->scheduled_at ?? '' ),
					'created_at'       => (string) ( $row->created_at ?? '' ),
					'student_user_id'  => (int) ( $row->student_user_id ?? 0 ),
					'tutor_user_id'    => (int) ( $row->tutor_user_id ?? 0 ),
					'duration_minutes' => (int) ( $row->duration_minutes ?? 0 ),
				];
			}
		}

		if ( $rows ) {
			return $rows;
		}

		return self::query_bookings_table_safe( $limit );
	}

	/**
	 * Safe direct table read when class helpers are unavailable.
	 *
	 * @param int $limit Max rows.
	 * @return array<int, array<string, mixed>>
	 */
	private static function query_bookings_table_safe( $limit = 25 ) {
		global $wpdb;
		if ( ! isset( $wpdb ) || ! is_object( $wpdb ) ) {
			return [];
		}

		$table = $wpdb->prefix . 'ngc_bookings';
		if ( class_exists( 'NGC_Database' ) && method_exists( 'NGC_Database', 'table' ) ) {
			$table = NGC_Database::table( 'bookings' );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( ! $exists ) {
			return [];
		}

		$limit = max( 1, min( 100, (int) $limit ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$raw = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, subject, status, scheduled_at, created_at, student_user_id, tutor_user_id, duration_minutes FROM {$table} ORDER BY scheduled_at DESC, id DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
		if ( ! is_array( $raw ) ) {
			return [];
		}

		$out = [];
		foreach ( $raw as $row ) {
			$out[] = [
				'id'               => (int) ( $row['id'] ?? 0 ),
				'booking_id'       => (int) ( $row['id'] ?? 0 ),
				'status'           => (string) ( $row['status'] ?? '' ),
				'subject'          => (string) ( $row['subject'] ?? '' ),
				'starts_at'        => (string) ( $row['scheduled_at'] ?? '' ),
				'created_at'       => (string) ( $row['created_at'] ?? '' ),
				'student_user_id'  => (int) ( $row['student_user_id'] ?? 0 ),
				'tutor_user_id'    => (int) ( $row['tutor_user_id'] ?? 0 ),
				'duration_minutes' => (int) ( $row['duration_minutes'] ?? 0 ),
			];
		}
		return $out;
	}

	/**
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function user_label( $user_id ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return '—';
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return '#' . $user_id;
		}
		return $user->display_name . ' (#' . $user_id . ')';
	}

	/**
	 * @param string $title   Title.
	 * @param string $summary Summary.
	 * @param string $content HTML.
	 */
	private static function page( $title, $summary, $content ) {
		if ( class_exists( 'NGC_Admin_Layout' ) ) {
			NGC_Admin_Layout::render_page(
				[
					'title'   => $title,
					'summary' => $summary,
					'content' => $content,
				]
			);
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html( $title ) . '</h1>' . $content . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
