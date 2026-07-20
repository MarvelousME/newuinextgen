<?php
/**
 * Tutor income calculator — interactive earnings estimator.
 *
 * @package NGT_UI
 */

declare(strict_types=1);

if ( ! class_exists( 'NGT_UI_Income_Calculator' ) ) {
	/**
	 * Full [ngt_income_calculator] shortcode component.
	 */
	class NGT_UI_Income_Calculator extends NGT_UI_Component_Base {

		public function get_name(): string {
			return 'income-calculator';
		}

		public function get_label(): string {
			return __( 'Tutor Income Calculator', 'ngt-ui' );
		}

		public function get_category(): string {
			return 'tools';
		}

		/**
		 * @return array<string, mixed>
		 */
		public function get_settings_schema(): array {
			return array(
				'title'           => array( 'type' => 'string', 'default' => __( 'Estimate your tutoring income', 'ngt-ui' ) ),
				'hours_per_week'  => array( 'type' => 'number', 'default' => 10 ),
				'hourly_rate'     => array( 'type' => 'number', 'default' => 350 ),
				'platform_fee'    => array( 'type' => 'number', 'default' => 15 ),
				'weeks_per_month' => array( 'type' => 'number', 'default' => 4.33 ),
				'currency'        => array( 'type' => 'string', 'default' => 'ZAR' ),
				'currency_symbol' => array( 'type' => 'string', 'default' => 'R' ),
				'class'           => array( 'type' => 'string', 'default' => '' ),
			);
		}

		/**
		 * @return array<int, string>
		 */
		public function get_style_dependencies(): array {
			return array( 'ngt-ui-income-calculator' );
		}

		/**
		 * @return array<int, string>
		 */
		public function get_script_dependencies(): array {
			return array( 'ngt-ui-income-calculator' );
		}

		/**
		 * @param array<string, mixed> $settings Settings.
		 * @param array<string, mixed> $context  Context.
		 */
		public function render( array $settings, array $context = array() ): string {
			$id     = $this->instance_id( 'ngt-income-calculator' );
			$class  = trim( 'ngt-ui-income-calculator ' . (string) ( $settings['class'] ?? '' ) );
			$title  = (string) ( $settings['title'] ?? '' );
			$hours  = max( 1, min( 60, (float) ( $settings['hours_per_week'] ?? 10 ) ) );
			$rate   = max( 0, (float) ( $settings['hourly_rate'] ?? 350 ) );
			$fee    = max( 0, min( 50, (float) ( $settings['platform_fee'] ?? 15 ) ) );
			$weeks  = max( 1, min( 5, (float) ( $settings['weeks_per_month'] ?? 4.33 ) ) );
			$symbol = (string) ( $settings['currency_symbol'] ?? 'R' );
			$currency = sanitize_key( (string) ( $settings['currency'] ?? 'ZAR' ) );

			$weekly_gross = $hours * $rate;
			$monthly_gross = $weekly_gross * $weeks;
			$monthly_net   = $monthly_gross * ( 1 - ( $fee / 100 ) );
			$annual_net    = $monthly_net * 12;

			ob_start();
			?>
			<div
				id="<?php echo esc_attr( $id ); ?>"
				class="<?php echo esc_attr( $class ); ?>"
				data-ngt-income-calculator
				data-currency="<?php echo esc_attr( $currency ); ?>"
				data-symbol="<?php echo esc_attr( $symbol ); ?>"
				data-weeks="<?php echo esc_attr( (string) $weeks ); ?>"
				style="--ngt-accent:var(--ngt-color-accent);--ngt-accent-2:var(--ngt-color-magic-accent-2);"
			>
				<?php if ( $title ) : ?>
					<h3 class="ngt-ui-ic__title"><?php echo esc_html( $title ); ?></h3>
				<?php endif; ?>

				<div class="ngt-ui-ic__grid">
					<label class="ngt-ui-ic__field">
						<span><?php esc_html_e( 'Hours per week', 'ngt-ui' ); ?></span>
						<input type="range" min="1" max="40" step="1" value="<?php echo esc_attr( (string) (int) $hours ); ?>" data-ngt-ic-hours />
						<output data-ngt-ic-hours-out><?php echo esc_html( (string) (int) $hours ); ?></output>
					</label>

					<label class="ngt-ui-ic__field">
						<span><?php esc_html_e( 'Hourly rate', 'ngt-ui' ); ?> (<?php echo esc_html( $symbol ); ?>)</span>
						<input type="number" min="50" max="2000" step="10" value="<?php echo esc_attr( (string) (int) $rate ); ?>" data-ngt-ic-rate />
					</label>

					<label class="ngt-ui-ic__field">
						<span><?php esc_html_e( 'Platform fee', 'ngt-ui' ); ?> (%)</span>
						<input type="range" min="0" max="30" step="1" value="<?php echo esc_attr( (string) (int) $fee ); ?>" data-ngt-ic-fee />
						<output data-ngt-ic-fee-out><?php echo esc_html( (string) (int) $fee ); ?>%</output>
					</label>
				</div>

				<div class="ngt-ui-ic__results" aria-live="polite">
					<div class="ngt-ui-ic__result">
						<span><?php esc_html_e( 'Weekly gross', 'ngt-ui' ); ?></span>
						<strong data-ngt-ic-weekly><?php echo esc_html( $symbol . ' ' . number_format_i18n( $weekly_gross, 0 ) ); ?></strong>
					</div>
					<div class="ngt-ui-ic__result ngt-ui-ic__result--highlight">
						<span><?php esc_html_e( 'Monthly net (after fee)', 'ngt-ui' ); ?></span>
						<strong data-ngt-ic-monthly><?php echo esc_html( $symbol . ' ' . number_format_i18n( $monthly_net, 0 ) ); ?></strong>
					</div>
					<div class="ngt-ui-ic__result">
						<span><?php esc_html_e( 'Annual net', 'ngt-ui' ); ?></span>
						<strong data-ngt-ic-annual><?php echo esc_html( $symbol . ' ' . number_format_i18n( $annual_net, 0 ) ); ?></strong>
					</div>
				</div>

				<p class="ngt-ui-ic__note">
					<?php esc_html_e( 'Estimates only. Actual earnings depend on bookings, subjects, and tutor tier.', 'ngt-ui' ); ?>
				</p>
			</div>
			<?php
			return (string) ob_get_clean();
		}
	}
}
