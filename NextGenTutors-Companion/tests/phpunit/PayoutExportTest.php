<?php
/**
 * @package NextGenCompanion
 */

use PHPUnit\Framework\TestCase;

/**
 * PayFast payout export CSV.
 */
class PayoutExportTest extends TestCase {

	public function test_to_csv_includes_header_and_row() {
		$csv = NGC_Payout_Export::to_csv(
			[
				[
					'recipient_email' => 'tutor@example.com',
					'recipient_name'  => 'Tutor',
					'amount'          => '99.50',
					'currency'        => 'ZAR',
					'reference'       => 'NGC-PAYOUT-7',
					'payout_id'       => 7,
				],
			]
		);
		$this->assertStringContainsString( 'recipient_email,recipient_name', $csv );
		$this->assertStringContainsString( 'tutor@example.com', $csv );
	}

	public function test_csv_cell_quotes_commas() {
		$this->assertSame( '"a,b"', NGC_Payout_Export::csv_cell( 'a,b' ) );
	}
}
