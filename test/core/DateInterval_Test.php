<?php
declare(strict_types=1);

namespace zesk;

class DateInterval_Test extends UnitTest {
	public function data_sample_interval_row(): array {
		return [
			['P1Y'], ['PT1S'], ['PT60S'], ['PT6000S'], ['PT3600S'], ['PT86400M'], ['P1D'], ['P2D'],
		];
	}

	public function data_extend(): array {
		$result = [];
		foreach ($this->data_sample_interval_row() as $sample_interval_row) {
			$sample_interval = $sample_interval_row[0];
			$result[] = [new DateInterval($sample_interval), new \DateInterval($sample_interval)];
		}
		return $result;
	}

	/**
	 * @param DateInterval $expected
	 * @param \DateInterval $input
	 * @return void
	 * @throws \Exception
	 * @dataProvider data_extend
	 */
	public function test_extend(DateInterval $expected, \DateInterval $input): void {
		$this->assertEquals(strval($expected), strval(DateInterval::extend($input)));
	}

	/**
	 * @param float $expected
	 * @param string $input
	 * @return void
	 * @dataProvider data_toSeconds
	 */
	public function test_toSeconds(string $input, float $expected): void {
		$this->assertEquals($expected, DateInterval::factory($input)->toSeconds(), "DateInterval::toSeconds($input)");
	}

	public function data_toSeconds(): array {
		return [
			['P1Y', Temporal::SECONDS_PER_YEAR], ['PT1S', 1.0], ['PT60S', 60.0], ['PT6000S', 6000.0],
			['PT3600S', 3600.0], ['PT86400M', 5184000.0],
			['P1D', 86400.0], ['P2D', 86400.0 * 2.0],
		];
	}
}