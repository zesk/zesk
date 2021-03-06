<?php
/**
 * @package zesk
 * @subpackage objects
 */
namespace zesk\Cron;

use zesk\HTML;
use zesk\StringTools;
use zesk\ArrayTools;
use zesk\Time;
use zesk\Date;
use zesk\Timestamp;
use zesk\Locale;
use zesk\Exception_Parse;
use zesk\Exception_Semantics;

/**
 * This needs to be cleaned up and probably broken into pieces.
 *
 * @author kent
 *
 */
class Parser {
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
	private $cron_codes = array();

	/**
	 *
	 * @var string
	 */
	private $phrase = null;

	/**
	 *
	 * @var integer
	 */
	const CRON_MINUTE = 0;

	/**
	 *
	 * @var integer
	 */
	const CRON_HOUR = 1;

	/**
	 *
	 * @var integer
	 */
	const CRON_MONTHDAY = 2;

	/**
	 *
	 * @var integer
	 */
	const CRON_MONTH = 3;

	/**
	 *
	 * @var integer
	 */
	const CRON_WEEKDAY = 4;

	/**
	 * Parse a string and convert it into a schedule
	 *
	 * @param string $text Some text in the locale's language
	 * @param string $locale The locale. If null, uses the current global locale.
	 */
	public function __construct($phrase, Locale $locale) {
		$this->locale = $locale;
		$this->phrase = $phrase;
		$this->cron_codes = array_fill(0, self::CRON_WEEKDAY + 1, null);
		$this->parse_language_en($phrase);
	}

	/**
	 *
	 * @param integer $index
	 * @param mixed $add
	 * @return self
	 */
	private function cron_add($index, $add) {
		if ($add === null) {
			return $this;
		}
		list($low, $high) = avalue(array(
			array(
				0,
				60,
			),
			array(
				0,
				23,
			),
			array(
				1,
				31,
			),
			array(
				1,
				12,
			),
			array(
				0,
				6,
			),
		), $index, array(
			null,
			null,
		));
		if ($low === null) {
			return $this;
		}
		if (is_numeric($add) && $add < $low || $add > $high) {
			return $this;
		}
		$old = avalue($this->cron_codes, $index, "*");
		if ($old === "*") {
			$this->cron_codes[$index] = $add;
			return $this;
		}
		$old = explode(",", $old);
		$old[] = $add;
		$old = array_unique($old);
		sort($old);
		$this->cron_codes[$index] = implode(",", $old);
		return $this;
	}

	/**
	 *
	 * @param Timestamp $now
	 * @throws Exception_Semantics
	 * @return \zesk\Timestamp
	 */
	public function compute_next(Timestamp $now) {
		list($cron_minute, $cron_hour, $cron_monthday, $cron_month, $cron_weekday) = $this->cron_codes;
		$match_list = array(
			array(
				$cron_minute,
				self::CRON_MINUTE,
				"minute",
				"hour",
				60,
			),
			array(
				$cron_hour,
				self::CRON_HOUR,
				"hour",
				"day",
				3600,
			),
			array(
				$cron_weekday,
				self::CRON_WEEKDAY,
				"weekday",
				null,
				-2,
			),
			array(
				$cron_monthday,
				self::CRON_MONTHDAY,
				"day",
				"month",
				86400,
			),
			array(
				$cron_month,
				self::CRON_MONTH,
				"month",
				"year",
				-1,
			),
		);
		$next = clone $now;
		$next->second(0);
		$default_match = array(
			"0",
			"0",
			"0",
			"0",
			"0",
		);
		$match = $default_match;
		$loops = 0;

		$debug = false;
		while (implode("", $match) !== "11111") {
			$loops++;
			$lower_units = array();
			foreach ($match_list as $params) {
				list($cron_value, $cindex, $unit, $next_unit, $divisor) = $params;
				if ($match[$cindex] === "0") {
					if ($cron_value === "*") {
						$match[$cindex] = "1";
					} elseif (($interval = $this->is_time_repeat($cron_value)) !== false) {
						assert($unit !== 'weekday');
						if ($divisor < 0) {
							$next_value = $next->month() + ($next->year() * 12);
						} else {
							$next_value = intval($next->unix_timestamp() / $divisor);
						}
						$mod = $next_value % $interval;
						if ($debug) {
							echo "$unit: $next_value % $interval = $mod<br />";
						}
						if ($mod === 0) {
							$match[$cindex] = "1";
						} else {
							$next->add_unit($$interval - $mod, $unit);
							$match = $default_match;
							$match[$cindex] = "1";

							break;
						}
					} else {
						if ($debug) {
							echo "$unit: " . $next->__toString() . "<br />";
						}
						$next_value = $next->unit($unit);
						$cron_values = explode(",", $cron_value);
						foreach ($cron_values as $cron_value) {
							if ($cron_value == $next_value) {
								$match[$cindex] = "1";

								break;
							} elseif ($cron_value > $next_value) {
								$next->unit($unit, $cron_value);
								foreach ($lower_units as $cindex_tmp => $lower_unit) {
									$next->unit($lower_unit, ($lower_unit == "day" || $lower_unit == "month") ? 1 : 0);
									$match[$cindex_tmp] = "0";
								}
								if ($debug) {
									echo "Setting unit $unit to $cron_value => " . $next->__toString() . "<br />";
								}
								$match[$cindex] = "1";

								break;
							}
						}
						if ($match[$cindex] !== "1") {
							$next->unit($unit, ($unit == "day" || $unit == "month") ? 1 : 0);
							if ($next_unit) {
								$next->add_unit(1, $next_unit);
							}
							$match = $default_match;

							break;
						}
					}
				}
				$lower_units[$cindex] = $unit;
				if (implode(",", $match) === "11111") {
					break 2;
				}
			}
			if ($loops > 100) {
				throw new Exception_Semantics("Infinite loop in Schedule next");
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
	private function parse_language_en($text) {
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
		$short_months = Date::month_names($locale, "en", true);
		$short_dow = Date::weekday_names($locale, "en", true);
		$short_months = ArrayTools::change_value_case($short_months);
		$short_dow = ArrayTools::change_value_case($short_dow);
		//		$original_text = $text;
		$text = preg_replace("/[,-]/", " ", strtolower(trim($text)));
		$text = preg_replace('/\s+/', " ", $text);
		$text = " $text ";
		$time_patterns = array(
			"weekday" => '(weekday)',
			"weekend" => '(weekend)',
			"unitsly" => "(hourly|monthly|daily|weekly)",
			"time-word" => "(?:at )?(midnight|noon|dusk|dawn|sunrise|sunset)",
			"pm-hint" => " (?:every |in the )?(night|afternoon|evening|eve|sunset|sunrise|afternoon) ",
			"am-hint" => " (?:every |in the )?(morning|sunrise|afternoon) ",
			"ampm-hint" => " (?:every |in the )(day|daytime) ",
			"units" => " (?:every )?([0-9]+|other) (min|minute|sec|second|hr|hour|day|week|month)s?",
			"units-opt" => " every ([0-9]+ |other )?(min|minute|sec|second|hr|hour|day|week|month)s?",
			"month-days" => "([1-3]?[0-9])(?:nd|st|th|rd)",
			"time" => "(?:at )?([0-9]{1,2})(?::([0-9]{2}))?( ?[ap]m?)?",
			"months" => '(' . strtolower(implode("|", Date::month_names($locale, "en"))) . ")",
			"short-months" => '(' . strtolower(implode("|", $short_months)) . ")",
			"dow" => '(' . strtolower(implode("|", Date::weekday_names($locale, "en"))) . ")",
			"short-dow" => '(' . implode("|", $short_dow) . ")",
			"years" => "([0-9]{4})",
		);

		$debug = false;
		if ($debug) {
			echo HTML::tag("h1", false, "$text");
		}
		$result = array();
		foreach ($time_patterns as $item => $pattern) {
			$matches = array();
			if (preg_match_all("/$pattern/i", $text, $matches, PREG_SET_ORDER)) {
				if ($debug) {
					echo "<h2>$item</h2>";
					dump($matches);
				}
				$result[$item] = $matches;
				foreach ($matches as $match) {
					$text = str_replace($match[0], "", $text);
				}
			}
		}
		$cron = array(
			"*",
			"*",
			"*",
			"*",
			"*",
		);
		$tod_hint = null;
		$need_time = false;
		$have_time = false;
		$need_dayofmonth = false;
		$have_dayofmonth = false;
		$extra_strings = array();
		foreach ($result as $item => $matches) {
			switch ($item) {
				case "am-hint":
					$tod_hint = "am";

					break;
				case "pm-hint":
					$tod_hint = "pm";

					break;
				case "ampm-hint":
					$tod_hint = null;

					break;
				case "time":
					foreach ($matches as $match) {
						$hh = intval($match[1]);
						$mm = avalue($match, 2, null);
						if (is_string($mm)) {
							$mm = intval($mm);
						}
						$ampm = aevalue($match, 3, $tod_hint);
						if ($hh <= 12) {
							if (substr($ampm, 0, 1) === "p") {
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
				case "time-word":
					foreach ($matches as $match) {
						list($hh, $mm) = avalue(array(
							"midnight" => array(
								0,
								0,
							),
							"noon" => array(
								12,
								0,
							),
							"dusk" => array(
								19,
								0,
							),
							"dawn" => array(
								7,
								0,
							),
							"sunrise" => array(
								7,
								0,
							),
							"sunset" => array(
								19,
								0,
							),
						), $match[1], array(
							null,
							null,
						));
						if ($hh !== null) {
							$this->cron_add(self::CRON_MINUTE, $mm);
							$this->cron_add(self::CRON_HOUR, $hh);
							$have_time = true;
						}
					}

					break;
				case "dow":
				case "short-dow":
					foreach ($matches as $match) {
						$x = array_search(substr($match[1], 0, 3), $short_dow);
						if ($x !== false) {
							$this->cron_add(self::CRON_WEEKDAY, $x);
							$need_time = true;
						}
					}

					break;
				case "weekday":
					for ($i = 1; $i <= 5; $i++) {
						$this->cron_add(self::CRON_WEEKDAY, $i);
					}

					break;
				case "weekend":
					$this->cron_add(self::CRON_WEEKDAY, 0);
					$this->cron_add(self::CRON_WEEKDAY, 6);

					break;
				case "months":
				case "short-months":
					foreach ($matches as $match) {
						$mm = array_search(substr($match[1], 0, 3), $short_months);
						if ($mm) {
							$this->cron_add(self::CRON_MONTH, $mm);
							$need_time = true;
						}
					}

					break;
				case "years":
					// Skip years for now
					break;
				case "month-days":
					foreach ($matches as $match) {
						$this->cron_add(self::CRON_MONTHDAY, $match[1]);
						$have_dayofmonth = true;
					}

					break;
				case "units":
				case "units-opt":
					foreach ($matches as $match) {
						if ($match[1] === "other") {
							$match[1] = 2;
						}
						if (empty($match[1])) {
							$match[1] = 1;
						}
						$n = intval($match[1]);
						if ($n !== 1) {
							switch ($match[2]) {
								case "minute":
								case "min":
									$this->cron_add(self::CRON_MINUTE, "*/$n");
									$have_time = true;

									break;
								case "hr":
								case "hour":
									$this->cron_add(self::CRON_HOUR, "*/$n");
									$have_time = true;

									break;
								case "day":
									$this->cron_add(self::CRON_MONTHDAY, "*/$n");
									$need_time = true;

									break;
								case "week":
									$this->cron_add(self::CRON_MONTHDAY, "*/" . ($n * 7));
									$need_time = true;

									break;
								case "month":
									$this->cron_add(self::CRON_MONTH, "*/$n");
									$need_time = true;

									break;
							}
						}
					}

					break;
				case "unitsly":
					foreach ($matches as $match) {
						switch ($match[1]) {
							case "hourly":
								$this->cron_add(self::CRON_MINUTE, 0);

								break;
							case "weekly":
								$this->cron_add(self::CRON_WEEKDAY, 0);
								$need_time = true;

								break;
							case "monthly":
								$this->cron_add(self::CRON_MONTHDAY, 1);
								$need_time = true;

								break;
							case "daily":
								$this->cron_add(self::CRON_HOUR, 0);
								$this->cron_add(self::CRON_MINUTE, 0);

								break;
						}
					}

					break;
				default:
					throw new Exception_Parse("Unknown pattern in schedule: $item");
			}
		}
		if (implode("", $cron) === "*****") {
			if (count($extra_strings) === 0) {
				return false;
			}
			return implode(";", $extra_strings);
		}
		if ($need_time && !$have_time) {
			$this->cron_add(self::CRON_HOUR, 0);
			$this->cron_add(self::CRON_MINUTE, 0);
		}
		if ($need_dayofmonth && !$have_dayofmonth) {
			$this->cron_add(self::CRON_MONTHDAY, 1);
		}
		if ($cron[0] === '*') {
			$cron[0] = "0";
		}
		if (is_numeric($cron[self::CRON_MONTH]) && is_numeric($cron[self::CRON_MONTHDAY])) {
			$cron[self::CRON_WEEKDAY] = "*";
		}
		$extra_strings[] = "cron:" . implode(" ", $cron);
		$result = implode(";", $extra_strings);
		if ($debug) {
			dump($result);
		}
		return $result;
	}

	private function days_to_language($code) {
		$items = explode(",", $code);
		$result = array();
		foreach ($items as $item) {
			if (is_numeric($item)) {
				$result[] = $this->locale->ordinal($item);
			}
		}
		return $this->locale->conjunction($result, $this->locale->__('and'));
	}

	private function months_to_language($code) {
		$items = explode(",", $code);
		$result = array();
		$months = Date::month_names($this->locale);
		foreach ($items as $item) {
			if (is_numeric($item)) {
				$result[] = $months[$item];
			}
		}
		return $this->locale->conjunction($result, $this->locale->__('and'));
	}

	private function dow_to_language($code, $locale, $plural = false) {
		if ($code === "1,2,3,4,5") {
			return $plural ? $this->locale->__('weekdays', $locale) : $this->locale->__('weekday', $locale);
		}
		if ($code === "0,6") {
			return $plural ? $this->locale->__('weekends', $locale) : $this->locale->__('weekend', $locale);
		}
		if ($code === "0,1,2,3,4,5,6") {
			return $this->locale->__('day', $locale);
		}
		$items = explode(",", $code);
		$result = array();
		$daysOfWeek = Date::weekday_names($locale);
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

	private function time_repeat_to_language($item, $unit, $locale) {
		if (($number = $this->is_time_repeat($item)) !== false) {
			$translate = ($this->locale)($number === 1 ? 'Schedule:=Every {1}' : 'Schedule:=Every {0} {1}', $locale);
			return map($translate, array(
				$number,
				$this->locale->plural($unit, $number, $locale),
			));
		}
		return false;
	}

	private function is_time_repeat($item) {
		$matches = false;
		if (preg_match('/\*\/([0-9]+)/', $item, $matches)) {
			return intval($matches[1]);
		}
		return false;
	}

	private function time_to_language($min, $hour) {
		$min = explode(",", $min);
		$hour = explode(",", $hour);
		$times = array();
		foreach ($hour as $h) {
			foreach ($min as $m) {
				if ($m == 0 && $h == 0) {
					$times[] = $this->locale->__('midnight');
				} elseif ($m == 0 && $h == 12) {
					$times[] = $this->locale->__('noon');
				} else {
					$t = new Time();
					if ($m == 0 && $h != intval($h)) {
						$t->minute(StringTools::right($h, "."));
						$t->hour(intval($h));
					} else {
						$t->minute($m);
						$t->hour($h);
					}
					$times[] = $t->format($this->locale, $this->locale->time_format());
				}
			}
		}
		return $this->locale->conjunction($times, $this->locale->__("and"));
	}

	/**
	 *
	 * @param unknown $data
	 * @param unknown $locale
	 * @return mixed|string|\zesk\Hookable|NULL|\zesk\NULL
	 */
	private function code_to_language_cron(Locale $locale) {
		list($min, $hour, $day, $month, $dow) = $this->cron_codes;
		$month_language = "";
		$dow_language = "";
		$time_every = false;
		$day_every = false;
		$plural_dow = false;
		if (($time_language = $this->time_repeat_to_language($min, "minute", $locale)) !== false) {
			$time_every = true;
		} elseif (($time_language = $this->time_repeat_to_language($hour, "hour", $locale)) !== false) {
			$time_every = true;
		}
		if (($day_language = $this->time_repeat_to_language($day, "day", $locale)) !== false) {
			$day_every = true;
		}
		switch (($day === "*" ? "*" : " ") . ($month === "*" ? "*" : " ") . ($dow === "*" ? "*" : " ")) {
			case "***":
				$translate_string = $time_every ? "{time}" : 'Schedule:=Every day at {time}';

				break;
			case "** ":
				$translate_string = $time_every ? "{time} on {weekday}" : __CLASS__ . ":=Every {weekday} at {time}";

				break;
			case "* *":
				$translate_string = __CLASS__ . ":=Every day in {month} at {time}";

				break;
			case "*  ":
				$plural_dow = true;
				$translate_string = $time_every ? __CLASS__ . ":={time} on {weekday} in {month}" : __CLASS__ . ":=At {time} on {weekday} in {month}";

				break;
			case " **":
				$translate_string = $day_every ? __CLASS__ . ":={day} at {time}" : __CLASS__ . ":=Every month on the {day} at {time}";

				break;
			case " * ":
				$translate_string = __CLASS__ . ":=Every month on the {day} at {time}, only on {weekday}";
				$plural_dow = true;

				break;
			case "  *":
				$translate_string = $day_every ? __CLASS__ . ":={day} in {month} at {time}" : __CLASS__ . ":={month} {day} at {time}";

				break;
			case "   ":
				$translate_string = __CLASS__ . ":={day} in {month}, on {weekday}, at {time}";
				$plural_dow = true;

				break;
		}
		if (!$time_language) {
			$time_language = $this->time_to_language($min, $hour);
		}
		if (!$day_language && $day !== "*") {
			$day_language = $this->days_to_language($day);
		}
		if ($month !== "*") {
			$month_language = $this->months_to_language($month);
		}
		if ($dow !== "*") {
			$dow_language = $this->dow_to_language($dow, $plural_dow);
		}
		$phrase = $this->locale->__($translate_string);
		return map($phrase, array(
			'time' => $time_language,
			'day' => $day_language,
			'month' => $month_language,
			'weekday' => $dow_language,
		));
	}

	// Format similar to crontab scheduling
	//
	// minute hour day-of-month month day-of-week
	//
	public function format() {
		return $this->code_to_language_cron($this->locale);
	}
}
