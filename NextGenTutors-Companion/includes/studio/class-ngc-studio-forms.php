<?php
/**
 * Visual form builder — field catalog, runtime, shortcodes.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Studio forms engine with hot-apply on save.
 */
class NGC_Studio_Forms {

	/** @var array<string, array<string, mixed>> */
	private static $published = [];

	/**
	 * Hook registration.
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register_shortcodes' ], 25 );
		add_action( 'admin_post_nopriv_ngc_studio_form_submit', [ __CLASS__, 'handle_submit' ] );
		add_action( 'admin_post_ngc_studio_form_submit', [ __CLASS__, 'handle_submit' ] );
		add_action( 'ngc_studio_forms_reload', [ __CLASS__, 'reload_published' ] );
		self::reload_published();
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	public static function field_catalog() {
		$fields = [
			'text'            => [ 'label' => 'Text', 'group' => 'basic' ],
			'number'          => [ 'label' => 'Number', 'group' => 'basic' ],
			'email'           => [ 'label' => 'Email', 'group' => 'basic' ],
			'phone'           => [ 'label' => 'Phone', 'group' => 'basic' ],
			'date'            => [ 'label' => 'Date', 'group' => 'basic' ],
			'time'            => [ 'label' => 'Time', 'group' => 'basic' ],
			'file'            => [ 'label' => 'File upload', 'group' => 'advanced' ],
			'signature'       => [ 'label' => 'Signature', 'group' => 'advanced' ],
			'checkbox'        => [ 'label' => 'Checkbox', 'group' => 'choice' ],
			'radio'           => [ 'label' => 'Radio', 'group' => 'choice' ],
			'select'          => [ 'label' => 'Select', 'group' => 'choice' ],
			'multiselect'     => [ 'label' => 'Multi-select', 'group' => 'choice' ],
			'repeater'        => [ 'label' => 'Repeater', 'group' => 'advanced' ],
			'address'         => [ 'label' => 'Address', 'group' => 'advanced' ],
			'richtext'        => [ 'label' => 'Rich text', 'group' => 'advanced' ],
			'subject_selector'=> [ 'label' => 'Subject selector', 'group' => 'tutoring' ],
			'grade_selector'  => [ 'label' => 'Grade selector', 'group' => 'tutoring' ],
			'location_selector'=> [ 'label' => 'Location selector', 'group' => 'tutoring' ],
			'tutor_selector'  => [ 'label' => 'Tutor selector', 'group' => 'tutoring' ],
			'parent_selector' => [ 'label' => 'Parent selector', 'group' => 'tutoring' ],
			'student_selector'=> [ 'label' => 'Student selector', 'group' => 'tutoring' ],
			'child_selector'  => [ 'label' => 'Child selector', 'group' => 'tutoring' ],
			'payment_selector'=> [ 'label' => 'Payment selector', 'group' => 'tutoring' ],
			'booking_selector'=> [ 'label' => 'Booking selector', 'group' => 'tutoring' ],
			'textarea'        => [ 'label' => 'Textarea', 'group' => 'basic' ],
		];
		return apply_filters( 'ngc_studio_form_field_catalog', $fields );
	}

	/**
	 * Save and hot-apply form.
	 *
	 * @param int                  $id   Form ID.
	 * @param array<string, mixed> $data Payload.
	 * @return array{ok:bool,form?:array<string,mixed>}
	 */
	public static function save_and_apply( $id, $data ) {
		$result = NGC_Studio_Repository::update_form( $id, $data );
		if ( empty( $result['ok'] ) ) {
			return $result;
		}
		$form = $result['form'];
		if ( $form && 'published' === ( $form['status'] ?? '' ) ) {
			self::$published[ (string) $form['form_key'] ] = $form;
		}
		do_action( 'ngc_studio_forms_reload' );
		return $result;
	}

	/**
	 * @param int $id Form ID.
	 * @return array{ok:bool,form?:array<string,mixed>}
	 */
	public static function publish( $id ) {
		$result = self::save_and_apply( $id, [ 'status' => 'published' ] );
		return $result;
	}

	/**
	 * Reload published forms into memory.
	 */
	public static function reload_published() {
		self::$published = [];
		foreach ( NGC_Studio_Repository::list_forms( 'published' ) as $form ) {
			self::$published[ (string) $form['form_key'] ] = $form;
		}
	}

	/**
	 * Register dynamic shortcodes for published forms.
	 */
	public static function register_shortcodes() {
		add_shortcode( 'ngc_studio_form', [ __CLASS__, 'shortcode' ] );
		foreach ( self::$published as $key => $form ) {
			add_shortcode( 'ngc_form_' . $key, static function () use ( $key ) {
				return NGC_Studio_Forms::render( $key );
			} );
		}
	}

	/**
	 * @param array<string, string> $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts( [ 'key' => '', 'id' => '' ], $atts, 'ngc_studio_form' );
		$key  = sanitize_key( (string) ( $atts['key'] ?: $atts['id'] ) );
		return self::render( $key );
	}

	/**
	 * @param string $key Form key.
	 * @return string
	 */
	public static function render( $key ) {
		$form = self::$published[ $key ] ?? NGC_Studio_Repository::get_form_by_key( $key );
		if ( ! $form ) {
			return '';
		}
		$fields = self::normalize_fields( $form );
		ob_start();
		?>
		<form class="ngc-studio-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="ngc_studio_form_submit" />
			<input type="hidden" name="form_key" value="<?php echo esc_attr( $key ); ?>" />
			<?php wp_nonce_field( 'ngc_studio_form_' . $key, 'ngc_studio_form_nonce' ); ?>
			<?php foreach ( $fields as $field ) : ?>
				<?php echo self::render_field( $field ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endforeach; ?>
			<button type="submit" class="ngc-studio-form__submit"><?php esc_html_e( 'Submit', 'nextgencompanion' ); ?></button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Handle form POST submission.
	 */
	public static function handle_submit() {
		$key = sanitize_key( (string) ( $_POST['form_key'] ?? '' ) );
		if ( ! $key || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ngc_studio_form_nonce'] ?? '' ) ), 'ngc_studio_form_' . $key ) ) {
			wp_die( esc_html__( 'Invalid form submission.', 'nextgencompanion' ) );
		}
		$form = self::$published[ $key ] ?? NGC_Studio_Repository::get_form_by_key( $key );
		if ( ! $form ) {
			wp_die( esc_html__( 'Form not found.', 'nextgencompanion' ) );
		}
		$payload = self::collect_payload( self::normalize_fields( $form ) );
		$errors  = self::validate_payload( self::normalize_fields( $form ), $payload );
		if ( $errors ) {
			wp_die( esc_html( implode( ' ', $errors ) ) );
		}

		do_action( 'ngc_studio_form_submitted', $key, $payload, $form );

		$workflow_id = (int) ( $form['workflow_id'] ?? 0 );
		if ( $workflow_id ) {
			$wf = NGC_Studio_Repository::get_workflow( $workflow_id );
			if ( $wf ) {
				NGC_Studio_Engine::execute( $wf, array_merge( $payload, [ 'form_key' => $key ] ), 'FORM_SUBMITTED', false );
			}
		}

		NGC_Studio_Event_Bus::emit( 'CUSTOM_EVENT', [ 'form_key' => $key, 'payload' => $payload ] );

		$redirect = (string) ( $form['settings']['redirect'] ?? home_url( '/' ) );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * REST submit handler.
	 *
	 * @param string               $key     Form key.
	 * @param array<string, mixed> $payload Payload.
	 * @return array{ok:bool,errors?:array<int,string>}
	 */
	public static function submit_rest( $key, $payload ) {
		$form = self::$published[ $key ] ?? NGC_Studio_Repository::get_form_by_key( $key );
		if ( ! $form || 'published' !== ( $form['status'] ?? '' ) ) {
			return [ 'ok' => false, 'errors' => [ __( 'Form not available.', 'nextgencompanion' ) ] ];
		}
		$errors = self::validate_payload( self::normalize_fields( $form ), $payload );
		if ( $errors ) {
			return [ 'ok' => false, 'errors' => $errors ];
		}
		do_action( 'ngc_studio_form_submitted', $key, $payload, $form );
		$workflow_id = (int) ( $form['workflow_id'] ?? 0 );
		if ( $workflow_id ) {
			$wf = NGC_Studio_Repository::get_workflow( $workflow_id );
			if ( $wf ) {
				NGC_Studio_Engine::execute( $wf, array_merge( $payload, [ 'form_key' => $key ] ), 'FORM_SUBMITTED', false );
			}
		}
		return [ 'ok' => true ];
	}

	/**
	 * @param array<string, mixed> $form Form row.
	 * @return array<int, array<string, mixed>>
	 */
	private static function normalize_fields( $form ) {
		$schema = (array) ( $form['schema'] ?? [] );
		if ( isset( $schema['fields'] ) && is_array( $schema['fields'] ) ) {
			return array_values( $schema['fields'] );
		}
		if ( isset( $schema[0] ) ) {
			return array_values( $schema );
		}
		return [];
	}

	/**
	 * @param array<string, mixed> $field Field def.
	 * @return string
	 */
	private static function render_field( $field ) {
		$type  = sanitize_key( (string) ( $field['type'] ?? 'text' ) );
		$name  = sanitize_key( (string) ( $field['name'] ?? $field['id'] ?? 'field' ) );
		$label = esc_html( (string) ( $field['label'] ?? $name ) );
		$req   = ! empty( $field['required'] ) ? ' required' : '';
		$id    = 'ngc-sf-' . $name;

		if ( in_array( $type, [ 'textarea', 'richtext', 'address' ], true ) ) {
			return '<label for="' . esc_attr( $id ) . '">' . $label . '</label><textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . $req . '></textarea>';
		}
		if ( in_array( $type, [ 'select', 'grade_selector', 'subject_selector', 'location_selector' ], true ) ) {
			$opts = (array) ( $field['options'] ?? [] );
			$html = '<label for="' . esc_attr( $id ) . '">' . $label . '</label><select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"' . $req . '>';
			foreach ( $opts as $opt ) {
				$html .= '<option value="' . esc_attr( (string) $opt ) . '">' . esc_html( (string) $opt ) . '</option>';
			}
			return $html . '</select>';
		}
		$input_type = in_array( $type, [ 'email', 'phone', 'number', 'date', 'time', 'file' ], true ) ? ( 'phone' === $type ? 'tel' : $type ) : 'text';
		return '<label for="' . esc_attr( $id ) . '">' . $label . '</label><input id="' . esc_attr( $id ) . '" type="' . esc_attr( $input_type ) . '" name="' . esc_attr( $name ) . '"' . $req . ' />';
	}

	/**
	 * @param array<int, array<string, mixed>> $fields Field defs.
	 * @return array<string, mixed>
	 */
	private static function collect_payload( $fields ) {
		$payload = [];
		foreach ( $fields as $field ) {
			$name = sanitize_key( (string) ( $field['name'] ?? '' ) );
			if ( ! $name ) {
				continue;
			}
			$payload[ $name ] = isset( $_POST[ $name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) : '';
		}
		return $payload;
	}

	/**
	 * @param array<int, array<string, mixed>> $fields  Fields.
	 * @param array<string, mixed>           $payload Payload.
	 * @return array<int, string>
	 */
	private static function validate_payload( $fields, $payload ) {
		$errors = [];
		foreach ( $fields as $field ) {
			$name = sanitize_key( (string) ( $field['name'] ?? '' ) );
			if ( ! $name || empty( $field['required'] ) ) {
				continue;
			}
			if ( empty( $payload[ $name ] ) ) {
				$errors[] = sprintf( __( '%s is required.', 'nextgencompanion' ), (string) ( $field['label'] ?? $name ) );
			}
		}
		return $errors;
	}

	/**
	 * Seed default studio forms.
	 */
	public static function seed_defaults() {
		if ( NGC_Studio_Repository::list_forms() ) {
			return;
		}
		NGC_Studio_Repository::create_form(
			[
				'form_key' => 'parent_intake',
				'name'     => 'Parent Intake Form',
				'status'   => 'published',
				'schema'   => [
					'fields' => [
						[ 'type' => 'text', 'name' => 'parent_name', 'label' => 'Parent name', 'required' => true ],
						[ 'type' => 'email', 'name' => 'email', 'label' => 'Email', 'required' => true ],
						[ 'type' => 'phone', 'name' => 'phone', 'label' => 'Phone', 'required' => true ],
						[ 'type' => 'grade_selector', 'name' => 'grade', 'label' => 'Grade', 'options' => [ 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12' ], 'required' => true ],
						[ 'type' => 'subject_selector', 'name' => 'subject', 'label' => 'Subject', 'options' => [ 'Maths', 'Science', 'English' ], 'required' => true ],
					],
				],
			]
		);
	}
}
