<?php
/**
 * Minimal admin settings.
 *
 * @package NextGen_3D_Filmstrip
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings page.
 */
final class NGTFS_Admin {

	const OPTION = 'ngtfs_settings';

	public static function init(): void {
		add_action( 'admin_menu', [ self::class, 'menu' ] );
		add_action( 'admin_init', [ self::class, 'register' ] );
	}

	public static function menu(): void {
		add_options_page(
			__( 'NextGen Filmstrip', 'nextgen-3d-filmstrip' ),
			__( 'NextGen Filmstrip', 'nextgen-3d-filmstrip' ),
			'manage_options',
			'ngtfs-settings',
			[ self::class, 'page' ]
		);
	}

	public static function register(): void {
		register_setting(
			'ngtfs_settings_group',
			self::OPTION,
			[
				'type'              => 'array',
				'sanitize_callback' => [ self::class, 'sanitize' ],
				'default'           => self::defaults(),
			]
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return [
			'default_source' => 'tutors',
			'default_limit'  => 8,
			'autoplay'       => 1,
		];
	}

	/**
	 * @param mixed $input Input.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : [];
		$out   = self::defaults();
		$src   = sanitize_key( (string) ( $input['default_source'] ?? 'tutors' ) );
		$out['default_source'] = in_array( $src, [ 'tutors', 'subjects' ], true ) ? $src : 'tutors';
		$out['default_limit']  = max( 1, min( 24, absint( $input['default_limit'] ?? 8 ) ) );
		$out['autoplay']       = empty( $input['autoplay'] ) ? 0 : 1;
		return $out;
	}

	public static function page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$opts = wp_parse_args( get_option( self::OPTION, [] ), self::defaults() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'NextGen 3D Filmstrip', 'nextgen-3d-filmstrip' ); ?></h1>
			<p><?php esc_html_e( 'Shortcode: [ngt_filmstrip source="tutors"] or [ngt_filmstrip source="subjects"]', 'nextgen-3d-filmstrip' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'ngtfs_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Default source', 'nextgen-3d-filmstrip' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( self::OPTION ); ?>[default_source]">
								<option value="tutors" <?php selected( $opts['default_source'], 'tutors' ); ?>><?php esc_html_e( 'Tutors', 'nextgen-3d-filmstrip' ); ?></option>
								<option value="subjects" <?php selected( $opts['default_source'], 'subjects' ); ?>><?php esc_html_e( 'Subjects', 'nextgen-3d-filmstrip' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default limit', 'nextgen-3d-filmstrip' ); ?></th>
						<td><input type="number" min="1" max="24" name="<?php echo esc_attr( self::OPTION ); ?>[default_limit]" value="<?php echo esc_attr( (string) $opts['default_limit'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Autoplay by default', 'nextgen-3d-filmstrip' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[autoplay]" value="1" <?php checked( ! empty( $opts['autoplay'] ) ); ?>> <?php esc_html_e( 'Enabled', 'nextgen-3d-filmstrip' ); ?></label></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<hr>
			<h2><?php esc_html_e( 'Diagnostics', 'nextgen-3d-filmstrip' ); ?></h2>
			<ul>
				<li><?php echo esc_html( 'Subjects available: ' . count( NGTFS_Data::subjects( 24 ) ) ); ?></li>
				<li><?php echo esc_html( 'Tutors available: ' . count( NGTFS_Data::tutors( 24 ) ) ); ?></li>
				<li><?php echo esc_html( 'subject taxonomy: ' . ( taxonomy_exists( 'subject' ) ? 'yes' : 'no' ) ); ?></li>
				<li><?php echo esc_html( 'bi_get_carousel_tutors: ' . ( function_exists( 'bi_get_carousel_tutors' ) ? 'yes' : 'no' ) ); ?></li>
			</ul>
		</div>
		<?php
	}
}
