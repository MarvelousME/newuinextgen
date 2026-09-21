<?php
/**
 * Server-side price + selection integrity.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Never trust frontend prices. Validate tutor/subject/duration/product consistency.
 */
class NGC_Session_Price_Integrity {

	/**
	 * Validate a purchase context against catalogue + booking.
	 *
	 * @param array<string, mixed> $input Input.
	 * @return array<string, mixed> Normalized context.
	 * @throws NGC_Session_Validation_Exception Invalid.
	 */
	public static function validate( array $input ) {
		$product_key = strtoupper( sanitize_text_field( (string) ( $input['product_key'] ?? '' ) ) );
		$def         = NGC_Product_Catalog::get( $product_key );
		if ( ! $def ) {
			throw new NGC_Session_Validation_Exception( 'Unknown tutoring product.', 'ngc_unknown_product', [ 'product_key' => $product_key ] );
		}

		$duration = (int) ( $input['duration_minutes'] ?? $def['duration_minutes'] );
		if ( $duration !== (int) $def['duration_minutes'] ) {
			throw new NGC_Session_Validation_Exception(
				'Purchased duration does not match the product.',
				'ngc_duration_mismatch',
				[ 'expected' => (int) $def['duration_minutes'], 'got' => $duration ]
			);
		}

		$count = (int) ( $input['session_count'] ?? $def['session_count'] );
		if ( $count !== (int) $def['session_count'] ) {
			throw new NGC_Session_Validation_Exception(
				'Session count does not match the product.',
				'ngc_session_count_mismatch',
				[ 'expected' => (int) $def['session_count'], 'got' => $count ]
			);
		}

		if ( isset( $input['price'] ) && $input['price'] !== '' && $input['price'] !== null ) {
			if ( ! NGC_Product_Catalog::price_matches( $product_key, $input['price'] ) ) {
				throw new NGC_Session_Validation_Exception(
					'Price does not match the WooCommerce product catalogue.',
					'ngc_price_tamper',
					[ 'expected' => $def['price'], 'got' => (string) $input['price'] ]
				);
			}
		}

		$tutor   = (int) ( $input['tutor_user_id'] ?? 0 );
		$student = (int) ( $input['student_user_id'] ?? 0 );
		$subject = sanitize_title( (string) ( $input['subject_id'] ?? $input['subject'] ?? '' ) );

		if ( $tutor <= 0 ) {
			throw new NGC_Session_Validation_Exception( 'Tutor is required.', 'ngc_tutor_required' );
		}
		if ( $student <= 0 ) {
			throw new NGC_Session_Validation_Exception( 'Student is required.', 'ngc_student_required' );
		}
		if ( '' === $subject ) {
			throw new NGC_Session_Validation_Exception( 'Subject is required.', 'ngc_subject_required' );
		}

		if ( ! empty( $input['booking_tutor_user_id'] ) && (int) $input['booking_tutor_user_id'] !== $tutor ) {
			throw new NGC_Session_Validation_Exception(
				'Selected tutor does not match the booking tutor.',
				'ngc_tutor_mismatch',
				[ 'selected' => $tutor, 'booking' => (int) $input['booking_tutor_user_id'] ]
			);
		}

		$booking_subject = sanitize_title( (string) ( $input['booking_subject'] ?? '' ) );
		if ( $booking_subject && $booking_subject !== $subject ) {
			throw new NGC_Session_Validation_Exception(
				'Selected subject does not match the booking subject.',
				'ngc_subject_mismatch',
				[ 'selected' => $subject, 'booking' => $booking_subject ]
			);
		}

		$currency = strtoupper( sanitize_text_field( (string) ( $input['currency'] ?? 'ZAR' ) ) );
		if ( 'ZAR' !== $currency ) {
			throw new NGC_Session_Validation_Exception( 'Unsupported currency.', 'ngc_currency_invalid', [ 'currency' => $currency ] );
		}

		return [
			'product_key'      => $product_key,
			'duration_minutes' => (int) $def['duration_minutes'],
			'session_count'    => (int) $def['session_count'],
			'package_type'     => (string) $def['package_type'],
			'format'           => (string) $def['format'],
			'price'            => (string) $def['price'],
			'currency'         => 'ZAR',
			'tutor_user_id'    => $tutor,
			'student_user_id'  => $student,
			'subject_id'       => $subject,
			'subject_name'     => sanitize_text_field( (string) ( $input['subject_name'] ?? $subject ) ),
		];
	}
}
