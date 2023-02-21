<?php
declare(strict_types=1);

namespace zesk;

class TemporalTest extends UnitTest {
	public static function data_convertUnits(): array {
		return [
			[0, 0, Temporal::UNIT_SECOND],
			[1, 1, Temporal::UNIT_SECOND],
			[1000.0, 1000.0, Temporal::UNIT_SECOND],
			[1.0, 60.0, Temporal::UNIT_MINUTE],
			[2.0, 120.0, Temporal::UNIT_MINUTE],
			[3.0, 180.0, Temporal::UNIT_MINUTE],
			[0.1, 360.0, Temporal::UNIT_HOUR],
			[1.0, 3600.0, Temporal::UNIT_HOUR],
			[1.0, 86400.0, Temporal::UNIT_DAY],
			[0.1, 8640.0, Temporal::UNIT_DAY],
			[1.0/7.0, 86400.0, Temporal::UNIT_WEEK],
		];
	}

	/**
	 * @param float $expected
	 * @param float $seconds
	 * @param string $toUnits
	 * @return void
	 * @throws KeyNotFound
	 * @dataProvider data_convertUnits
	 */
	public function test_convertUnits(float $expected, float $seconds, string $toUnits): void {
		$this->assertEquals($expected, Temporal::convertUnits($seconds, $toUnits), "Temporal::convertUnits($seconds, $toUnits)");
	}
}
