<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/cron/classes/legacy/schedule.inc $
 * @package zesk
 * @subpackage objects
 */
zesk()->obsolete();

use zesk\Timestamp;
use zesk\Locale;

/**
 * 
 * @author kent
 *
 */
class Schedule {
	const cron_minute = 0;
	const cron_hour = 1;
	const cron_monthday = 2;
	const cron_month = 3;
	const cron_weekday = 4;
	private static function cron_add($cron, $index, $add) {
		if ($add === null) {
			return $cron;
		}
		list($low, $high) = avalue(array(
			array(
				0,
				60
			),
			array(
				0,
				23
			),
			array(
				1,
				31
			),
			array(
				1,
				12
			),
			array(
				0,
				6
			)
		), $index, array(
			null,
			null
		));
		if ($low === null) {
			return $cron;
		}
		if (is_numeric($add) && $add < $low || $add > $high) {
			return $cron;
		}
		$old = avalue($cron, $index, "*");
		if ($old === "*") {
			$cron[$index] = $add;
			return $cron;
		}
		$old = explode(",", $old);
		$old[] = $add;
		$old = array_unique($old);
		sort($old);
		$cron[$index] = implode(",", $old);
		return $cron;
	}
	private static function compute_next_cron(Timestamp $now, $data) {
		list($cron_minute, $cron_hour, $cron_monthday, $cron_month, $cron_weekday) = explode(" ", $data);
		
		$match_list = array(
			array(
				$cron_minute,
				self::cron_minute,
				"minute",
				"hour",
				60
			),
			array(
				$cron_hour,
				self::cron_hour,
				"hour",
				"day",
				3600
			),
			array(
				$cron_weekday,
				self::cron_weekday,
				"weekday",
				null,
				-2
			),
			array(
				$cron_monthday,
				self::cron_monthday,
				"day",
				"month",
				86400
			),
			array(
				$cron_month,
				self::cron_month,
				"month",
				"year",
				-1
			)
		);
		$next = clone $now;
		$next->second(0);
		$default_match = array(
			"0",
			"0",
			"0",
			"0",
			"0"
		);
		$match = $default_match;
		$loops = 0;
		$debug = _G('debug-schedule-next');
		if ($debug) {
			dump($data);
		}
		while (implode("", $match) !== "11111") {
			$loops++;
			$lower_units = array();
			foreach ($match_list as $params) {
				list($cron_value, $cindex, $unit, $next_unit, $divisor) = $params;
				if ($match[$cindex] === "0") {
					if ($cron_value === "*") {
						$match[$cindex] = "1";
					} else if (($interval = self::is_time_repeat($cron_value)) !== false) {
						assert($unit !== 'weekday');
						if ($divisor < 0) {
							$next_value = $next->getMonth() + ($next->getYear() * 12);
						} else {
							$next_value = intval($next->toTimestamp() / $divisor);
						}
						$mod = $next_value % $interval;
						if ($debug) {
							echo "$unit: $next_value % $interval = $mod<br />";
						}
						if ($mod === 0) {
							$match[$cindex] = "1";
						} else {
							$next->addUnit($unit, $interval - $mod);
							$match = $default_match;
							$match[$cindex] = "1";
							break;
						}
					} else {
						if ($debug) {
							echo "$unit: " . $next->__toString() . "<br />";
						}
						$next_value = $next->getUnit($unit);
						$cron_values = explode(",", $cron_value);
						foreach ($cron_values as $cron_value) {
							if ($cron_value == $next_value) {
								$match[$cindex] = "1";
								break;
							} else if ($cron_value > $next_value) {
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
								$next->addUnit($next_unit, 1);
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
				throw new Exception("Infinite loop in Schedule next");
			}
		}
		return $next;
	}
	static function compute_next($code, $relative_time = "now") {
		$now = new Timestamp();
		$now->set($relative_time);
		$codes = explode(";", $code);
		foreach ($codes as $code) {
			list($schema, $data) = pair($code, ":", null, $code);
			switch ($schema) {
				case "cron":
					return self::compute_next_cron($now, $data);
			}
		}
		return null;
	}
	
	/**
	 * Parse a string and convert it into a schedule
	 *
	 * @param string $text Some text in the locale's language
	 * @param string $locale The locale. If null, uses the current global locale.
	 */
	private static function parse_language_en($text) {
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
		$short_months = Date::month_names("en", true);
		$short_dow = Date::weekday_names("en", true);
		$short_months = arr::change_value_case($short_months);
		$short_dow = arr::change_value_case($short_dow);
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
			"months" => '(' . strtolower(implode("|", Date::month_names("en"))) . ")",
			"short-months" => '(' . strtolower(implode("|", $short_months)) . ")",
			"dow" => '(' . strtolower(implode("|", Date::weekday_names("en"))) . ")",
			"short-dow" => '(' . implode("|", $short_dow) . ")",
			"years" => "([0-9]{4})"
		);
		
		$debug = zesk()->configuration->path_get("Schedule::debug_parsing");
		if ($debug) {
			echo HTML::tag("h1", false, "$text");
		}
		$result = array();
		foreach ($time_patterns as $item => $pattern) {
			$matches = false;
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
			"*"
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
							} else if ($ampm === null) {
								// 12-hour time
								if ($hh < 6) {
									$hh += 12;
								}
							}
						}
						//$cron = self::cron_add($cron, self::cron_minute, $mm);
						$cron = self::cron_add($cron, self::cron_hour, "$hh.$mm");
						$have_time = true;
					}
					break;
				case "time-word":
					foreach ($matches as $match) {
						list($hh, $mm) = avalue(array(
							"midnight" => array(
								0,
								0
							),
							"noon" => array(
								12,
								0
							),
							"dusk" => array(
								19,
								0
							),
							"dawn" => array(
								7,
								0
							),
							"sunrise" => array(
								7,
								0
							),
							"sunset" => array(
								19,
								0
							)
						), $match[1], array(
							null,
							null
						));
						if ($hh !== null) {
							$cron = self::cron_add($cron, self::cron_minute, $mm);
							$cron = self::cron_add($cron, self::cron_hour, $hh);
							$have_time = true;
						}
					}
					break;
				case "dow":
				case "short-dow":
					foreach ($matches as $match) {
						$x = array_search(substr($match[1], 0, 3), $short_dow);
						if ($x !== false) {
							$cron = self::cron_add($cron, self::cron_weekday, $x);
							$need_time = true;
						}
					}
					break;
				case "weekday":
					for ($i = 1; $i <= 5; $i++) {
						$cron = self::cron_add($cron, self::cron_weekday, $i);
					}
					break;
				case "weekend":
					$cron = self::cron_add($cron, self::cron_weekday, 0);
					$cron = self::cron_add($cron, self::cron_weekday, 6);
					break;
				case "months":
				case "short-months":
					foreach ($matches as $match) {
						$mm = array_search(substr($match[1], 0, 3), $short_months);
						if ($mm) {
							$cron = self::cron_add($cron, self::cron_month, $mm);
							$need_time = true;
						}
					}
					break;
				case "years":
					// Skip years for now
					break;
				case "month-days":
					foreach ($matches as $match) {
						$cron = self::cron_add($cron, self::cron_monthday, $match[1]);
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
									$cron = self::cron_add($cron, self::cron_minute, "*/$n");
									$have_time = true;
									break;
								case "hr":
								case "hour":
									$cron = self::cron_add($cron, self::cron_hour, "*/$n");
									$have_time = true;
									break;
								case "day":
									$cron = self::cron_add($cron, self::cron_monthday, "*/$n");
									$need_time = true;
									break;
								case "week":
									$cron = self::cron_add($cron, self::cron_monthday, "*/" . ($n * 7));
									$need_time = true;
									break;
								case "month":
									$cron = self::cron_add($cron, self::cron_month, "*/$n");
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
								$cron = self::cron_add($cron, self::cron_minute, 0);
								break;
							case "weekly":
								$cron = self::cron_add($cron, self::cron_weekday, 0);
								$need_time = true;
								break;
							case "monthly":
								$cron = self::cron_add($cron, self::cron_monthday, 1);
								$need_time = true;
								break;
							case "daily":
								$cron = self::cron_add($cron, self::cron_hour, 0);
								$cron = self::cron_add($cron, self::cron_minute, 0);
								break;
						}
					}
					break;
				default :
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
			$cron = self::cron_add($cron, self::cron_hour, 0);
			$cron = self::cron_add($cron, self::cron_minute, 0);
		}
		if ($need_dayofmonth && !$have_dayofmonth) {
			$cron = self::cron_add($cron, self::cron_monthday, 1);
		}
		if ($cron[0] === '*') {
			$cron[0] = "0";
		}
		if (is_numeric($cron[self::cron_month]) && is_numeric($cron[self::cron_monthday])) {
			$cron[self::cron_weekday] = "*";
		}
		$extra_strings[] = "cron:" . implode(" ", $cron);
		$result = implode(";", $extra_strings);
		if ($debug) {
			dump($result);
		}
		return $result;
	}
	
	/**
	 * Parse a string and convert it into a schedule
	 *
	 * @param string $text Some text in the locale's language
	 * @param string $locale The locale. If null, uses the current global locale.
	 */
	static function parse($text, $locale = null) {
		if (!$locale) {
			$locale = _G('locale');
		}
		switch (strtolower(substr($locale, 0, 2))) {
			default :
				break;
		}
		return self::parse_language_en($text);
	}
	private static function days_to_language($code, $locale) {
		$items = explode(",", $code);
		$result = array();
		foreach ($items as $item) {
			if (is_numeric($item)) {
				$result[] = Locale::ordinal($item, $locale);
			}
		}
		return Locale::conjunction($result, __('and', $locale));
	}
	private static function months_to_language($code, $locale) {
		$items = explode(",", $code);
		$result = array();
		$months = Date::monthNames($locale);
		foreach ($items as $item) {
			if (is_numeric($item)) {
				$result[] = $months[$item];
			}
		}
		return Locale::conjunction($result, __('and', $locale));
	}
	private static function dow_to_language($code, $locale, $plural = false) {
		if ($code === "1,2,3,4,5") {
			return $plural ? __('weekdays', $locale) : __('weekday', $locale);
		}
		if ($code === "0,6") {
			return $plural ? __('weekends', $locale) : __('weekend', $locale);
		}
		if ($code === "0,1,2,3,4,5,6") {
			return __('day', $locale);
		}
		$items = explode(",", $code);
		$result = array();
		$daysOfWeek = Date::daysOfTheWeek($locale);
		foreach ($items as $item) {
			if (is_numeric($item)) {
				if ($plural) {
					$result[] = Locale::plural($daysOfWeek[$item]);
				} else {
					$result[] = $daysOfWeek[$item];
				}
			}
		}
		return Locale::conjunction($result, __('and', $locale));
	}
	private static function time_repeat_to_language($item, $unit, $locale) {
		if (($number = self::is_time_repeat($item)) !== false) {
			$translate = Locale::translate($number === 1 ? 'Schedule:=Every {1}' : 'Schedule:=Every {0} {1}', $locale);
			return map($translate, array(
				$number,
				Locale::plural($unit, $number, $locale)
			));
		}
		return false;
	}
	private static function is_time_repeat($item) {
		$matches = false;
		if (preg_match('/\*\/([0-9]+)/', $item, $matches)) {
			return intval($matches[1]);
		}
		return false;
	}
	private static function time_to_language($min, $hour, $locale) {
		$min = explode(",", $min);
		$hour = explode(",", $hour);
		$times = array();
		foreach ($hour as $h) {
			foreach ($min as $m) {
				if ($m == 0 && $h == 0) {
					$times[] = __('midnight');
				} else if ($m == 0 && $h == 12) {
					$times[] = __('noon');
				} else {
					$t = new Time();
					if ($m == 0 && $h != intval($h)) {
						$t->minute(str::right($h, "."));
						$t->hour(intval($h));
					} else {
						$t->minute($m);
						$t->hour($h);
					}
					$times[] = $t->format(Locale::time_format($locale));
				}
			}
		}
		return Locale::conjunction($times, __("and", $locale));
	}
	private static function code_to_language_cron($data, $locale) {
		list($min, $hour, $day, $month, $dow) = explode(" ", $data, 5);
		$month_language = "";
		$dow_language = "";
		$time_every = false;
		$day_every = false;
		$plural_dow = false;
		if (($time_language = self::time_repeat_to_language($min, "minute", $locale)) !== false) {
			$time_every = true;
		} else if (($time_language = self::time_repeat_to_language($hour, "hour", $locale)) !== false) {
			$time_every = true;
		}
		if (($day_language = self::time_repeat_to_language($day, "day", $locale)) !== false) {
			$day_every = true;
		}
		switch (($day === "*" ? "*" : " ") . ($month === "*" ? "*" : " ") . ($dow === "*" ? "*" : " ")) {
			case "***":
				$translate_string = $time_every ? "{time}" : 'Schedule:=Every day at {time}';
				break;
			case "** ":
				$translate_string = $time_every ? "{time} on {weekday}" : "Schedule:=Every {weekday} at {time}";
				break;
			case "* *":
				$translate_string = "Schedule:=Every day in {month} at {time}";
				break;
			case "*  ":
				$plural_dow = true;
				$translate_string = $time_every ? "Schedule:={time} on {weekday} in {month}" : "Schedule:=At {time} on {weekday} in {month}";
				break;
			case " **":
				$translate_string = $day_every ? "Schedule:={day} at {time}" : "Schedule:=Every month on the {day} at {time}";
				break;
			case " * ":
				$translate_string = "Schedule:=Every month on the {day} at {time}, only on {weekday}";
				$plural_dow = true;
				break;
			case "  *":
				$translate_string = $day_every ? "Schedule:={day} in {month} at {time}" : "Schedule:={month} {day} at {time}";
				break;
			case "   ":
				$translate_string = "Schedule:={day} in {month}, on {weekday}, at {time}";
				$plural_dow = true;
				break;
		}
		if (!$time_language) {
			$time_language = self::time_to_language($min, $hour, $locale);
		}
		if (!$day_language && $day !== "*") {
			$day_language = self::days_to_language($day, $locale);
		}
		if ($month !== "*") {
			$month_language = self::months_to_language($month, $locale);
		}
		if ($dow !== "*") {
			$dow_language = self::dow_to_language($dow, $locale, $plural_dow);
		}
		$phrase = Locale::translate($translate_string, $locale);
		return map($phrase, array(
			'time' => $time_language,
			'day' => $day_language,
			'month' => $month_language,
			'weekday' => $dow_language
		));
	}
	
	// Format similar to crontab scheduling
	//
	// minute hour day-of-month month day-of-week
	//
	static function format($code, $locale = null) {
		$codes = explode(";", $code);
		$result = array();
		foreach ($codes as $code) {
			list($schema, $data) = pair($code, ":", null, $code);
			switch ($schema) {
				case "cron":
					$result[] = self::code_to_language_cron($data, $locale);
					break;
			}
		}
		
		return implode(";", $result);
	}
}

