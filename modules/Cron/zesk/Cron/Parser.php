<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage objects
 */
namespace zesk\Cron;

use zesk\ArrayTools;
use zesk\Date;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;
use zesk\HTML;
use zesk\Locale\Locale;
use zesk\StringTools;
use zesk\Time;
use zesk\Timestamp;

/**
 * This needs to be cleaned up and probably broken into pieces.
 *
 * @author kent
 *
 */
class Parser
{
	/**
	 *
	 * @var boolean
	 */
	private $debug = false;

	/**
	 *
	 * @var Locale
	 */
	private $locale = null;

	/**
	 * String formatted
	 * @var string
	 */
	private $cron_codes = [];

	/**
	 *
	 * @var string
	 */
	private string $phrase = '';

	/**
	 *
	 * @var integer
	 */
	public const CRON_MINUTE = 0;

	/**
	 *
	 * @var integer
	 */
	public const CRON_HOUR = 1;

	/**
	 *
	 * @var integer
	 */
	public const CRON_MONTHDAY = 2;

	/**
	 *
	 * @var integer
	 */
	public const CRON_MONTH = 3;

	/**
	 *
	 * @var integer
	 */
	public const CRON_WEEKDAY = 4;

	/**
	 * Parse a string and convert it into a schedule
	 *
	 * @param string $text Some text in the locale's language
	 * @param string $locale The locale. If null, uses the current global locale.
	 */
	public function __construct(string $phrase, Locale $locale)
	{
		$this->locale = $locale;
		$this->phrase = $phrase;
		$this->cron_codes = array_fill(0, self::CRON_WEEKDAY + 1, null);
		$this->parse_language_en($phrase);
	}

	/**
	 *
	 * @param int $index
	 * @param mixed $add
	 * @return self
	 */
	private function cron_add($index, $add)
	{
		if ($add === null) {
			return $this;
		}
		[$low, $high] = [
			[
				0,
				60,
			],
			[
				0,
				23,
			],
			[
				1,
				31,
			],
			[
				1,
				12,
			],
			[
				0,
				6,
			],
		][$index] ?? [
			null,
			null,
		];
		if ($low === null) {
			return $this;
		}
		if (is_numeric($add) && $add < $low || $add > $high) {
			return $this;
		}
		$old = $this->cron_codes[$index] ?? '*';
		if ($old === '*') {
			$this->cron_codes[$index] = $add;
			return $this;
		}
		$old = explode(',', $old);
		$old[] = $add;
		$old = array_unique($old);
		sort($old);
		$this->cron_codes[$index] = implode(',', $old);
		return $this;
	}

	/**
	 *
	 * @param Timestamp $now
	 * @return Timestamp
	 * @throws SemanticsException
	 */
	public function compute_next(Timestamp $now): Timestamp
	{
		[$cron_minute, $cron_hour, $cron_monthday, $cron_month, $cron_weekday] = $this->cron_codes;
		$match_list = [
			[
				$cron_minute,
				self::CRON_MINUTE,
				'minute',
				'hour',
				60,
			],
			[
				$cron_hour,
				self::CRON_HOUR,
				'hour',
				'day',
				3600,
			],
			[
				$cron_weekday,
				self::CRON_WEEKDAY,
				'weekday',
				null,
				-2,
			],
			[
				$cron_monthday,
				self::CRON_MONTHDAY,
				'day',
				'month',
				86400,
			],
			[
				$cron_month,
				self::CRON_MONTH,
				'month',
				'year',
				-1,
			],
		];
		$next = clone $now;
		$next->setSecond(0);
		$default_match = [
			'0',
			'0',
			'0',
			'0',
			'0',
		];
		$match = $default_match;
		$loops = 0;

		$debug = false;
		while (implode('', $match) !== '11111') {
			$loops++;
			$lower_units = [];
			foreach ($match_list as $params) {
				[$cron_value, $cindex, $unit, $next_unit, $divisor] = $params;
				if ($match[$cindex] === '0') {
					if ($cron_value === '*') {
						$match[$cindex] = '1';
					} elseif (($interval = $this->is_time_repeat($cron_value)) !== false) {
						assert($unit !== 'weekday');
						if ($divisor < 0) {
							$next_value = $next->month() + ($next->year() * 12);
						} else {
							$next_value = intval($next->unixTimestamp() / $divisor);
						}
						$mod = $next_value % $interval;
						if ($debug) {
							echo "$unit: $next_value % $interval = $mod<br />";
						}
						if ($mod === 0) {
							$match[$cindex] = '1';
						} else {
							$next->addUnit($$interval - $mod, $unit);
							$match = $default_match;
							$match[$cindex] = '1';

							break;
						}
					} else {
						if ($debug) {
							echo "$unit: " . $next->__toString() . '<br />';
						}
						$next_value = $next->unit($unit);
						$cron_values = explode(',', $cron_value);
						foreach ($cron_values as $cron_value) {
							if ($cron_value == $next_value) {
								$match[$cindex] = '1';

								break;
							} elseif ($cron_value > $next_value) {
								$next->setUnit($unit, intval($cron_value));
								foreach ($lower_units as $cindex_tmp => $lower_unit) {
									$next->setUnit($lower_unit, ($lower_unit == 'day' || $lower_unit == 'month') ? 1 :
										0);
									$match[$cindex_tmp] = '0';
								}
								if ($debug) {
									echo "Setting unit $unit to $cron_value => " . $next->__toString() . '<br />';
								}
								$match[$cindex] = '1';

								break;
							}
						}
						if ($match[$cindex] !== '1') {
							$next->setUnit($unit, ($unit == 'day' || $unit == 'month') ? 1 : 0);
							if ($next_unit) {
								$next->addUnit(1, $next_unit);
							}
							$match = $default_match;

							break;
						}
					}
				}
				$lower_units[$cindex] = $unit;
				if (implode(',', $match) === '11111') {
					break 2;
				}
			}
			if ($loops > 100) {
				throw new SemanticsException('Infinite loop in Schedule next');
			}
		}
		return $next;
	}

	/**
	 * Parse a string and convert it into a schedule
	 *
	 * @param string $text Some text in the locale's language
	 * @param string $locale The locale. If null, uses the current global locale.
	 */
	private function parse_language_en($text)
	{
		$locale = $this->locale;
		// Examples:
		// 	Every [night|day|morning|afternoon|evening] at [midnight|noon|time-value]
		//  Every Monday
		//  On mondays
		//  On mondays, tuesdays, and thursdays at [time]
		//  On mondays in August
		//  Every week on Monday, Wednesday, and Friday
		//  The first of April, May, and June
		//  The 1st of aug, sept, and oct
		//  next week
		//  last week
		//  June 2009
		//  September 2010
		//  Every 10 minutes
		//  Every 3 days
		$short_months = Date::monthNames($locale, true);
		$short_dow = Date::weekdayNames($locale, true);
		$short_months = ArrayTools::changeValueCase($short_months);
		$short_dow = ArrayTools::changeValueCase($short_dow);
		//		$original_text = $text;
		$text = preg_replace('/[,-]/', ' ', strtolower(trim($text)));
		$text = preg_replace('/\s+/', ' ', $text);
		$text = " $text ";
		$time_patterns = [
			'weekday' => '(weekday)',
			'weekend' => '(weekend)',
			'unitsly' => '(hourly|monthly|daily|weekly)',
			'time-word' => '(?:at )?(midnight|noon|dusk|dawn|sunrise|sunset)',
			'pm-hint' => ' (?:every |in the )?(night|afternoon|evening|eve|sunset|sunrise|afternoon) ',
			'am-hint' => ' (?:every |in the )?(morning|sunrise|afternoon) ',
			'ampm-hint' => ' (?:every |in the )(day|daytime) ',
			'units' => ' (?:every )?([0-9]+|other) (min|minute|sec|second|hr|hour|day|week|month)s?',
			'units-opt' => ' every ([0-9]+ |other )?(min|minute|sec|second|hr|hour|day|week|month)s?',
			'month-days' => '([1-3]?[0-9])(?:nd|st|th|rd)',
			'time' => '(?:at )?([0-9]{1,2})(?::([0-9]{2}))?( ?[ap]m?)?',
			'months' => '(' . strtolower(implode('|', Date::monthNames($locale))) . ')',
			'short-months' => '(' . strtolower(implode('|', $short_months)) . ')',
			'dow' => '(' . strtolower(implode('|', Date::weekdayNames($locale))) . ')',
			'short-dow' => '(' . implode('|', $short_dow) . ')',
			'years' => '([0-9]{4})',
		];

		$debug = false;
		if ($debug) {
			echo HTML::tag('h1', [], "$text");
		}
		$result = [];
		foreach ($time_patterns as $item => $pattern) {
			$matches = [];
			if (preg_match_all("/$pattern/i", $text, $matches, PREG_SET_ORDER)) {
				if ($debug) {
					echo "<h2>$item</h2>";
					dump($matches);
				}
				$result[$item] = $matches;
				foreach ($matches as $match) {
					$text = str_replace($match[0], '', $text);
				}
			}
		}
		$cron = [
			'*',
			'*',
			'*',
			'*',
			'*',
		];
		$tod_hint = null;
		$need_time = false;
		$have_time = false;
		$need_dayofmonth = false;
		$have_dayofmonth = false;
		foreach ($result as $item => $matches) {
			switch ($item) {
				case 'am-hint':
					$tod_hint = 'am';

					break;
				case 'pm-hint':
					$tod_hint = 'pm';

					break;
				case 'ampm-hint':
					$tod_hint = null;

					break;
				case 'time':
					foreach ($matches as $match) {
						$hh = intval($match[1]);
						$mm = $match[2] ?? null;
						if (is_string($mm)) {
							$mm = intval($mm);
						}
						$ampm = $match[3] ?? $tod_hint;
						if ($hh <= 12) {
							if (str_starts_with($ampm, 'p')) {
								$hh += 12;
							} elseif ($ampm === null) {
								// 12-hour time
								if ($hh < 6) {
									$hh += 12;
								}
							}
						}
						$cron = $this->cron_add(self::CRON_HOUR, "$hh.$mm");
						$have_time = true;
					}

					break;
				case 'time-word':
					foreach ($matches as $match) {
						[$hh, $mm] = [
							'midnight' => [
								0,
								0,
							],
							'noon' => [
								12,
								0,
							],
							'dusk' => [
								19,
								0,
							],
							'dawn' => [
								7,
								0,
							],
							'sunrise' => [
								7,
								0,
							],
							'sunset' => [
								19,
								0,
							],
						][$match[1]] ?? [
							null,
							null,
						];
						if ($hh !== null) {
							$this->cron_add(self::CRON_MINUTE, $mm);
							$this->cron_add(self::CRON_HOUR, $hh);
							$have_time = true;
						}
					}

					break;
				case 'dow':
				case 'short-dow':
					foreach ($matches as $match) {
						$x = array_search(substr($match[1], 0, 3), $short_dow);
						if ($x !== false) {
							$this->cron_add(self::CRON_WEEKDAY, $x);
							$need_time = true;
						}
					}

					break;
				case 'weekday':
					for ($i = 1; $i <= 5; $i++) {
						$this->cron_add(self::CRON_WEEKDAY, $i);
					}

					break;
				case 'weekend':
					$this->cron_add(self::CRON_WEEKDAY, 0);
					$this->cron_add(self::CRON_WEEKDAY, 6);

					break;
				case 'months':
				case 'short-months':
					foreach ($matches as $match) {
						$mm = array_search(substr($match[1], 0, 3), $short_months);
						if ($mm) {
							$this->cron_add(self::CRON_MONTH, $mm);
							$need_time = true;
						}
					}

					break;
				case 'years':
					// Skip years for now
					break;
				case 'month-days':
					foreach ($matches as $match) {
						$this->cron_add(self::CRON_MONTHDAY, $match[1]);
						$have_dayofmonth = true;
					}

					break;
				case 'units':
				case 'units-opt':
					foreach ($matches as $match) {
						if ($match[1] === 'other') {
							$match[1] = 2;
						}
						if (empty($match[1])) {
							$match[1] = 1;
						}
						$n = intval($match[1]);
						if ($n !== 1) {
							switch ($match[2]) {
								case 'minute':
								case 'min':
									$this->cron_add(self::CRON_MINUTE, "*/$n");
									$have_time = true;

									break;
								case 'hr':
								case 'hour':
									$this->cron_add(self::CRON_HOUR, "*/$n");
									$have_time = true;

									break;
								case 'day':
									$this->cron_add(self::CRON_MONTHDAY, "*/$n");
									$need_time = true;

									break;
								case 'week':
									$this->cron_add(self::CRON_MONTHDAY, '*/' . ($n * 7));
									$need_time = true;

									break;
								case 'month':
									$this->cron_add(self::CRON_MONTH, "*/$n");
									$need_time = true;

									break;
							}
						}
					}

					break;
				case 'unitsly':
					foreach ($matches as $match) {
						switch ($match[1]) {
							case 'hourly':
								$this->cron_add(self::CRON_MINUTE, 0);

								break;
							case 'weekly':
								$this->cron_add(self::CRON_WEEKDAY, 0);
								$need_time = true;

								break;
							case 'monthly':
								$this->cron_add(self::CRON_MONTHDAY, 1);
								$need_time = true;

								break;
							case 'daily':
								$this->cron_add(self::CRON_HOUR, 0);
								$this->cron_add(self::CRON_MINUTE, 0);

								break;
						}
					}

					break;
				default:
					throw new ParseException("Unknown pattern in schedule: $item");
			}
		}
		if (implode('', $cron) === '*****') {
			return false;
		}
		if ($need_time && !$have_time) {
			$this->cron_add(self::CRON_HOUR, 0);
			$this->cron_add(self::CRON_MINUTE, 0);
		}
		if ($need_dayofmonth && !$have_dayofmonth) {
			$this->cron_add(self::CRON_MONTHDAY, 1);
		}
		if ($cron[0] === '*') {
			$cron[0] = '0';
		}
		if (is_numeric($cron[self::CRON_MONTH]) && is_numeric($cron[self::CRON_MONTHDAY])) {
			$cron[self::CRON_WEEKDAY] = '*';
		}
		$result = 'cron:' . implode(' ', $cron);
		if ($debug) {
			dump($result);
		}
		return $result;
	}

	private function days_to_language($code)
	{
		$items = explode(',', $code);
		$result = [];
		foreach ($items as $item) {
			if (is_numeric($item)) {
				$result[] = $this->locale->ordinal($item);
			}
		}
		return $this->locale->conjunction($result, $this->locale->__('and'));
	}

	private function months_to_language($code)
	{
		$items = explode(',', $code);
		$result = [];
		$months = Date::monthNames($this->locale);
		foreach ($items as $item) {
			if (is_numeric($item)) {
				$result[] = $months[$item];
			}
		}
		return $this->locale->conjunction($result, $this->locale->__('and'));
	}

	private function dow_to_language($code, $locale, $plural = false)
	{
		if ($code === '1,2,3,4,5') {
			return $plural ? $this->locale->__('weekdays', $locale) : $this->locale->__('weekday', $locale);
		}
		if ($code === '0,6') {
			return $plural ? $this->locale->__('weekends', $locale) : $this->locale->__('weekend', $locale);
		}
		if ($code === '0,1,2,3,4,5,6') {
			return $this->locale->__('day', $locale);
		}
		$items = explode(',', $code);
		$result = [];
		$daysOfWeek = Date::weekdayNames($locale);
		foreach ($items as $item) {
			if (is_numeric($item)) {
				if ($plural) {
					$result[] = $this->locale->plural($daysOfWeek[$item]);
				} else {
					$result[] = $daysOfWeek[$item];
				}
			}
		}
		return $this->locale->conjunction($result, $this->locale->__('and', $locale));
	}

	private function time_repeat_to_language($item, $unit, $locale)
	{
		if (($number = $this->is_time_repeat($item)) !== false) {
			$translate = ($this->locale)($number === 1 ? 'Schedule:=Every {1}' : 'Schedule:=Every {0} {1}', $locale);
			return map($translate, [
				$number,
				$this->locale->plural($unit, $number),
			]);
		}
		return false;
	}

	private function is_time_repeat($item)
	{
		$matches = false;
		if (preg_match('/\*\/([0-9]+)/', $item, $matches)) {
			return intval($matches[1]);
		}
		return false;
	}

	private function time_to_language($min, $hour)
	{
		$min = explode(',', $min);
		$hour = explode(',', $hour);
		$times = [];
		foreach ($hour as $h) {
			foreach ($min as $m) {
				if ($m == 0 && $h == 0) {
					$times[] = $this->locale->__('midnight');
				} elseif ($m == 0 && $h == 12) {
					$times[] = $this->locale->__('noon');
				} else {
					$t = new Time();
					if ($m == 0 && $h != intval($h)) {
						$t->setMinute(intval(StringTools::right($h, '.')));
						$t->setHour(intval($h));
					} else {
						$t->setMinute(intval($m));
						$t->setHour(intval($h));
					}
					$times[] = $t->format($this->locale, $this->locale->formatTime());
				}
			}
		}
		return $this->locale->conjunction($times, $this->locale->__('and'));
	}

	/**
	 *
	 * @param Locale $locale
	 * @return array
	 */
	private function code_to_language_cron(Locale $locale): array
	{
		[$min, $hour, $day, $month, $dow] = $this->cron_codes;
		$month_language = '';
		$dow_language = '';
		$time_every = false;
		$day_every = false;
		$plural_dow = false;
		if (($time_language = $this->time_repeat_to_language($min, 'minute', $locale)) !== false) {
			$time_every = true;
		} elseif (($time_language = $this->time_repeat_to_language($hour, 'hour', $locale)) !== false) {
			$time_every = true;
		}
		if (($day_language = $this->time_repeat_to_language($day, 'day', $locale)) !== false) {
			$day_every = true;
		}
		switch (($day === '*' ? '*' : ' ') . ($month === '*' ? '*' : ' ') . ($dow === '*' ? '*' : ' ')) {
			case '***':
				$translate_string = $time_every ? '{time}' : 'Schedule:=Every day at {time}';

				break;
			case '** ':
				$translate_string = $time_every ? '{time} on {weekday}' : __CLASS__ . ':=Every {weekday} at {time}';

				break;
			case '* *':
				$translate_string = __CLASS__ . ':=Every day in {month} at {time}';

				break;
			case '*  ':
				$plural_dow = true;
				$translate_string = $time_every ? __CLASS__ . ':={time} on {weekday} in {month}' : __CLASS__ . ':=At {time} on {weekday} in {month}';

				break;
			case ' **':
				$translate_string = $day_every ? __CLASS__ . ':={day} at {time}' : __CLASS__ . ':=Every month on the {day} at {time}';

				break;
			case ' * ':
				$translate_string = __CLASS__ . ':=Every month on the {day} at {time}, only on {weekday}';
				$plural_dow = true;

				break;
			case '  *':
				$translate_string = $day_every ? __CLASS__ . ':={day} in {month} at {time}' : __CLASS__ . ':={month} {day} at {time}';

				break;
			case '   ':
				$translate_string = __CLASS__ . ':={day} in {month}, on {weekday}, at {time}';
				$plural_dow = true;

				break;
		}
		if (!$time_language) {
			$time_language = $this->time_to_language($min, $hour);
		}
		if (!$day_language && $day !== '*') {
			$day_language = $this->days_to_language($day);
		}
		if ($month !== '*') {
			$month_language = $this->months_to_language($month);
		}
		if ($dow !== '*') {
			$dow_language = $this->dow_to_language($dow, $plural_dow);
		}
		$phrase = $this->locale->__($translate_string);
		return map($phrase, [
			'time' => $time_language,
			'day' => $day_language,
			'month' => $month_language,
			'weekday' => $dow_language,
		]);
	}

	// Format similar to crontab scheduling
	//
	// minute hour day-of-month month day-of-week
	//
	public function format()
	{
		return $this->code_to_language_cron($this->locale);
	}

	public function variables(): array
	{
		return [
			'phrase' => $this->phrase,
			'locale' => $this->locale,
		];
	}
}
