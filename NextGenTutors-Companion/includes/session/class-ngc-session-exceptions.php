<?php
/**
 * Typed session-domain exceptions.
 *
 * @package NextGenCompanion
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base session exception with classification + correlation.
 */
class NGC_Session_Exception extends Exception {

	public const CLASS_VALIDATION      = 'Validation';
	public const CLASS_AUTHORIZATION   = 'Authorization';
	public const CLASS_PAYMENT         = 'Payment';
	public const CLASS_BOOKING         = 'Booking';
	public const CLASS_COMMERCE        = 'Commerce';
	public const CLASS_MASTERSTUDY     = 'MasterStudy';
	public const CLASS_MEETING         = 'Meeting';
	public const CLASS_DATABASE        = 'Database';
	public const CLASS_EXTERNAL        = 'ExternalProvider';
	public const CLASS_CONFIGURATION   = 'Configuration';
	public const CLASS_CONCURRENCY     = 'Concurrency';

	/** @var string */
	protected $classification = self::CLASS_VALIDATION;

	/** @var string */
	protected $error_code = 'ngc_session_error';

	/** @var bool */
	protected $retryable = false;

	/** @var array<string, mixed> */
	protected $context = [];

	/**
	 * @param string               $message        User-safe message.
	 * @param string               $error_code     Machine code.
	 * @param string               $classification Class.
	 * @param array<string, mixed> $context        Context (no secrets).
	 * @param bool                 $retryable      Retry classification.
	 */
	public function __construct( $message, $error_code = 'ngc_session_error', $classification = self::CLASS_VALIDATION, array $context = [], $retryable = false ) {
		parent::__construct( (string) $message );
		$this->error_code      = (string) $error_code;
		$this->classification  = (string) $classification;
		$this->context         = $context;
		$this->retryable       = (bool) $retryable;
	}

	/** @return string */
	public function get_error_code() {
		return $this->error_code;
	}

	/** @return string */
	public function get_classification() {
		return $this->classification;
	}

	/** @return bool */
	public function is_retryable() {
		return $this->retryable;
	}

	/** @return array<string, mixed> */
	public function get_context() {
		return $this->context;
	}

	/**
	 * @return WP_Error
	 */
	public function to_wp_error() {
		return new WP_Error(
			$this->error_code,
			$this->getMessage(),
			array_merge(
				[
					'status'         => $this->http_status(),
					'classification' => $this->classification,
					'retryable'      => $this->retryable,
				],
				$this->context
			)
		);
	}

	/**
	 * @return int
	 */
	protected function http_status() {
		if ( self::CLASS_AUTHORIZATION === $this->classification ) {
			return 403;
		}
		if ( self::CLASS_VALIDATION === $this->classification ) {
			return 400;
		}
		if ( self::CLASS_CONCURRENCY === $this->classification ) {
			return 409;
		}
		return 500;
	}
}

/**
 * Invalid input / state.
 */
class NGC_Session_Validation_Exception extends NGC_Session_Exception {
	public function __construct( $message, $error_code = 'ngc_session_validation', array $context = [] ) {
		parent::__construct( $message, $error_code, self::CLASS_VALIDATION, $context, false );
	}
}

/**
 * IDOR / RBAC failure.
 */
class NGC_Session_Authorization_Exception extends NGC_Session_Exception {
	public function __construct( $message, $error_code = 'ngc_session_forbidden', array $context = [] ) {
		parent::__construct( $message, $error_code, self::CLASS_AUTHORIZATION, $context, false );
	}
}

/**
 * Payment / commerce failure.
 */
class NGC_Session_Payment_Exception extends NGC_Session_Exception {
	public function __construct( $message, $error_code = 'ngc_session_payment', array $context = [], $retryable = true ) {
		parent::__construct( $message, $error_code, self::CLASS_PAYMENT, $context, $retryable );
	}
}

/**
 * Booking provider failure.
 */
class NGC_Session_Booking_Exception extends NGC_Session_Exception {
	public function __construct( $message, $error_code = 'ngc_session_booking', array $context = [], $retryable = true ) {
		parent::__construct( $message, $error_code, self::CLASS_BOOKING, $context, $retryable );
	}
}

/**
 * MasterStudy failure.
 */
class NGC_Session_Learning_Exception extends NGC_Session_Exception {
	public function __construct( $message, $error_code = 'ngc_session_masterstudy', array $context = [], $retryable = true ) {
		parent::__construct( $message, $error_code, self::CLASS_MASTERSTUDY, $context, $retryable );
	}
}

/**
 * Meeting provider failure.
 */
class NGC_Session_Meeting_Exception extends NGC_Session_Exception {
	public function __construct( $message, $error_code = 'ngc_session_meeting', array $context = [], $retryable = true ) {
		parent::__construct( $message, $error_code, self::CLASS_MEETING, $context, $retryable );
	}
}

/**
 * Invalid lifecycle transition.
 */
class NGC_Session_Transition_Exception extends NGC_Session_Validation_Exception {
	public function __construct( $from, $to ) {
		parent::__construct(
			sprintf( 'Invalid session transition: %s → %s', (string) $from, (string) $to ),
			'ngc_session_invalid_transition',
			[ 'from' => (string) $from, 'to' => (string) $to ]
		);
	}
}
