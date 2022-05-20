<?php declare(strict_types=1);
/**
 *
 */
use zesk\preg;
use zesk\Text;
use zesk\Test_Unit;

/**
 *
 * @author kent
 *
 */
class RRule_Set_Test extends Test_Unit {
	protected array $load_modules = [
		'iCalendar',
	];

	public function load_rrule_tests($tests_path) {
		$tests_content = file_get_contents($tests_path);
		$tests = [];
		$test = [];
		$attr = [
			'description',
			'rrule',
			'result',
		];
		$attrindex = 0;
		$blank = null;
		$lines = Text::remove_line_comments($tests_content);
		$lines = explode("\n", $lines);
		$content = '';
		foreach ($lines as $line) {
			$line = trim($line);
			if (empty($line)) {
				if ($blank === true) {
					continue;
				} else {
					$blank = true;
					if ($content) {
						$test[$attr[$attrindex]] = trim($content);
						$attrindex = ($attrindex + 1) % count($attr);
						$content = '';
						if ($attrindex === 0) {
							$tests[] = $test;
							$test = [];
						}
					}
				}
			} else {
				$blank = false;
				$content .= $line . "\n";
			}
		}
		if ($content) {
			$test[$attr[$attrindex]] = trim($content);
		}
		if (count($test) > 0) {
			$tests[] = $test;
		}
		return $tests;
	}

	public function test_rrules(): void {
		$locale = $this->application->locale_registry('en');
		$tests_path = $this->application->modules->path('icalendar', 'test/test-data/rrule-tests.txt');
		$tests = $this->load_rrule_tests($tests_path);
		foreach ($tests as $test_index => $test) {
			$description = $rrule = $result = null;
			extract($test, EXTR_IF_EXISTS);

			if ($test_index === 36) {
				// KMD TODO DEBUGGING
				$forever = false;
			}

			$parser = new RRule\Parser();
			$ruleset = $parser->parse($rrule);
			$results = $this->parse_results($result);
			$total = count($results);
			$index = 0;
			$forever = false;
			foreach ($ruleset->iterator() as $ts) {
				$expected = array_shift($results);
				++$index;
				if ($expected === true) {
					$forever = true;

					break;
				}
				$this->assert_equal($ts->format($locale, '{YYYY}-{MM}-{DD} {hh}:{mm}:{ss} {ZZZ}'), $expected, "Test #$test_index, result #$index: $description\n$rrule");
			}
			if (!$forever) {
				$this->assert_equal($index, $total, "Test #$test_index: $description\n\nIterator stopped before results used up: \n\n\t" . implode("\n\t", $results) . "\n\nRule:\n\n$rrule\n");
			}
		}
	}

	public function parse_results($result) {
		$results = [];
		$lines = explode("\n", $result);
		while (count($lines) > 0) {
			$line = array_shift($lines);
			$line = trim($line);
			if (begins($line, '==')) {
				break;
			}
			if ($line === '...') {
				$results[] = true;

				break;
			}
			$extrapolated = [];
			foreach (($preg = preg::matches('#\[([-,0-9]+)\]#', $line)) as $match) {
				$series = $match[1];
				$extrapolated = [];
				if (preg_match('#([0-9]+)-([0-9]+)#', $series, $range)) {
					$size = max(strlen($range[1]), strlen($range[2]));
					$start = intval($range[1]);
					$end = intval($range[2]);
					for ($i = $start; $i <= $end; $i++) {
						$repl = Text::ralign("$i", $size, '0');
						$extrapolated[] = str_replace($match[0], $repl, $line);
					}
				} else {
					foreach (explode(',', $series) as $repl) {
						$extrapolated[] = str_replace($match[0], $repl, $line);
					}
				}
			}
			if (count($extrapolated) === 0) {
				$results[] = $line;
			} else {
				$lines = array_merge($extrapolated, $lines);
			}
		}
		sort($results);
		return $results;
	}
}
