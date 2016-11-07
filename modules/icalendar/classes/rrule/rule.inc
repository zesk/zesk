<?php
/**
 * Implementation of iCalendar RRULE
 *
 * @author kent
 * @copyright &copy; 2016 Market Acumen, Inc.
 *
 * http://www.ietf.org/rfc/rfc2445.txt
 *
 * Based on python-dateutil, just like everyone else's version
 *
 * https://pypi.python.org/pypi/python-dateutil/2.5.3
 * 
 * see module's README.txt for license information.
 */
namespace RRule;

use zesk\Timestamp;
use zesk\Exception_Range;
use zesk\Exception_Parameter;
use zesk\Exception_Semantics;
use zesk\Time;

class Rule {
	/**
	 *
	 * @var string
	 */
	const FREQUENCY_YEARLY = "YEARLY";
	/**
	 *
	 * @var string
	 */
	const FREQUENCY_MONTHLY = "MONTHLY";
	/**
	 *
	 * @var string
	 */
	const FREQUENCY_WEEKLY = "WEEKLY";
	
	/**
	 *
	 * @var string
	 */
	const FREQUENCY_DAILY = "DAILY";
	
	/**
	 *
	 * @var string
	 */
	const FREQUENCY_HOURLY = "HOURLY";
	/**
	 *
	 * @var string
	 */
	const FREQUENCY_MINUTELY = "MINUTELY";
	/**
	 *
	 * @var string
	 */
	const FREQUENCY_SECONDLY = "SECONDLY";
	
	/**
	 *
	 * @var boolean
	 */
	private static $inited = false;
	/**
	 *
	 * @var array
	 */
	public static $MMASK = array(
		0 => null,
		1 => null
	);
	/**
	 *
	 * @var array
	 */
	public static $MDAYMASK = array(
		0 => null,
		1 => null
	);
	/**
	 *
	 * @var array
	 */
	public static $NMDAYMASK = array(
		0 => null,
		1 => null
	);
	
	/**
	 *
	 * @var array
	 */
	public static $MRANGE = array(
		0 => array(
			0,
			31,
			60,
			91,
			121,
			152,
			182,
			213,
			244,
			274,
			305,
			335,
			366
		),
		1 => array(
			0,
			31,
			59,
			90,
			120,
			151,
			181,
			212,
			243,
			273,
			304,
			334,
			365
		)
	);
	
	/**
	 * Days of the week
	 *
	 * @var array
	 */
	private static $dow = array(
		'MO',
		'TU',
		'WE',
		'TH',
		'FR',
		'SA',
		'SU'
	);
	
	/**
	 * Days of the week code to integer DOW
	 *
	 * @var array
	 */
	private static $dow_flip = null;
	
	/**
	 * 
	 * @var integer
	 */
	const FREQUENCY_INDEX_YEARLY = 0;
	/**
	 * 
	 * @var integer
	 */
	const FREQUENCY_INDEX_MONTHLY = 1;
	/**
	 * 
	 * @var integer
	 */
	const FREQUENCY_INDEX_WEEKLY = 2;
	/**
	 * 
	 * @var integer
	 */
	const FREQUENCY_INDEX_DAILY = 3;
	/**
	 * 
	 * @var integer
	 */
	const FREQUENCY_INDEX_HOURLY = 4;
	/**
	 * 
	 * @var integer
	 */
	const FREQUENCY_INDEX_MINUTELY = 5;
	/**
	 * 
	 * @var integer
	 */
	const FREQUENCY_INDEX_SECONDLY = 6;
	/**
	 * 
	 * @var array
	 */
	private static $frequencies = array(
		self::FREQUENCY_YEARLY => FREQUENCY_INDEX_YEARLY,
		self::FREQUENCY_MONTHLY => FREQUENCY_INDEX_MONTHLY,
		self::FREQUENCY_WEEKLY => FREQUENCY_INDEX_WEEKLY,
		self::FREQUENCY_DAILY => FREQUENCY_INDEX_DAILY,
		self::FREQUENCY_HOURLY => FREQUENCY_INDEX_HOURLY,
		self::FREQUENCY_MINUTELY => FREQUENCY_INDEX_MINUTELY,
		self::FREQUENCY_SECONDLY => FREQUENCY_INDEX_SECONDLY
	);
	
	/**
	 * 
	 * @var Timestamp
	 */
	private $dtstart = null;
	
	/**
	 * 
	 * @var Timestamp
	 */
	private $until = null;
	/**
	 * 
	 * @var string
	 */
	private $frequency = null;
	/**
	 * 
	 * @var integer
	 */
	private $frequency_index = null;
	/**
	 *
	 * @var integer
	 */
	private $interval = null;
	
	/**
	 *
	 * @var integer
	 */
	private $count = null;
	
	/**
	 * @var integer|integer-list
	 */
	private $wkst = null;
	/**
	 * @var integer|integer-list
	 */
	private $bysetpos = null;
	
	/**
	 * @var integer|integer-list
	 */
	private $bymonth = null;
	
	/**
	 * @var integer|integer-list
	 */
	private $byyearday = null;
	
	/**
	 * Month day list
	 * 
	 * @var integer|integer-list
	 */
	private $bymonthday = null;
	/**
	 * @var integer|integer-list
	 */
	private $bynmonthday = null;
	/**
	 * @var integer|integer-list
	 */
	private $byweekno = null;
	
	/**
	 * @var integer|integer-list
	 */
	private $byweekday = null;
	/**
	 * @var integer|integer-list
	 */
	private $bynweekday = null;
	/**
	 * @var integer|integer-list
	 */
	private $byhour = null;
	/**
	 * @var integer|integer-list
	 */
	private $byminute = null;
	/**
	 * @var integer|integer-list
	 */
	private $bysecond = null;
	
	/**
	 * @var Time[]
	 */
	private $timeset = null;
	
	/**
	 * 
	 * @var array
	 */
	private $original_rule = null;
	
	/**
	 *
	 *   That's the base of the rrule operation. It accepts all the keywords
	 *   defined in the RFC as its constructor parameters (except byday,
	 *   which was renamed to byweekday) and more. The constructor prototype is::
	 *	
	 *           new \RRule\Rule(freq)
	 *	
	 *   Where freq must be one of YEARLY, MONTHLY, WEEKLY, DAILY, HOURLY, MINUTELY,
	 *   or SECONDLY.
	 *	
	 *   .. note::
	 *       Per RFC section 3.3.10, recurrence instances falling on invalid dates
	 *       and times are ignored rather than coerced:
	 *	
	 *           Recurrence rules may generate recurrence instances with an invalid
	 *           date (e.g., February 30) or nonexistent local time (e.g., 1:30 AM
	 *           on a day where the local time is moved forward by an hour at 1:00
	 *           AM).  Such recurrence instances MUST be ignored and MUST NOT be
	 *           counted as part of the recurrence set.
	 *	
	 *       This can lead to possibly surprising behavior when, for example, the
	 *       start date occurs at the end of the month:
	 *	
	 *       >>> from dateutil.rrule import rrule, MONTHLY
	 *       >>> from datetime import datetime
	 *       >>> start_date = datetime(2014, 12, 31)
	 *       >>> list(rrule(freq=MONTHLY, count=4, dtstart=start_date))
	 *       ... # doctest: +NORMALIZE_WHITESPACE
	 *       [datetime.datetime(2014, 12, 31, 0, 0),
	 *        datetime.datetime(2015, 1, 31, 0, 0),
	 *        datetime.datetime(2015, 3, 31, 0, 0),
	 *        datetime.datetime(2015, 5, 31, 0, 0)]
	 *	
	 *   Additionally, it supports the following keyword arguments:
	 *	
	 *  ["dtstart" => Timestamp ]
	 *       The recurrence start. Besides being the base for the recurrence,
	 *       missing parameters in the final recurrence instances will also be
	 *       extracted from this date. If not given, datetime.now() will be used
	 *       instead.
	 *  ["interval" => integer ]
	 *       The interval between each freq iteration. For example, when using
	 *       YEARLY, an interval of 2 means once every two years, but with HOURLY,
	 *       it means once every two hours. The default interval is 1.
	 *  ["wkst" => integer|string ]
	 *       The week start day. Must be one of the MO, TU, WE constants, or an
	 *       integer, specifying the first day of the week. This will affect
	 *       recurrences based on weekly periods. The default week start is got
	 *       from calendar.firstweekday(), and may be modified by
	 *       calendar.setfirstweekday().
	 *  ["count" => integer ]
	 *       How many occurrences will be generated.
	 *	
	 *       .. note::
	 *           As of version 2.5.0, the use of the ``until`` keyword together
	 *           with the ``count`` keyword is deprecated per RFC-2445 Sec. 4.3.10.
	 *  ["until" => Timestamp ]
	 *       If given, this must be a Timestamp instance, that will specify the
	 *       limit of the recurrence. The last recurrence in the rule is the greatest
	 *       datetime that is less than or equal to the value specified in the
	 *       ``until`` parameter.
	 *	
	 *       .. note::
	 *           As of version 2.5.0, the use of the ``until`` keyword together
	 *           with the ``count`` keyword is deprecated per RFC-2445 Sec. 4.3.10.
	 *  [ "bysetpos" => integer|integer list ]
	 *       If given, it must be either an integer, or a sequence of integers,
	 *       positive or negative. Each given integer will specify an occurrence
	 *       number, corresponding to the nth occurrence of the rule inside the
	 *       frequency period. For example, a bysetpos of -1 if combined with a
	 *       MONTHLY frequency, and a byweekday of (MO, TU, WE, TH, FR), will
	 *       result in the last work day of every month.
	 *  [ "bymonth" => integer|integer list ]:
	 *       If given, it must be either an integer, or a sequence of integers,
	 *       meaning the months to apply the recurrence to.
	 *  [ "bymonthday" => integer|integer list ]:
	 *       If given, it must be either an integer, or a sequence of integers,
	 *       meaning the month days to apply the recurrence to.
	 *  [ "byyearday" => integer|integer list ]:
	 *       If given, it must be either an integer, or a sequence of integers,
	 *       meaning the year days to apply the recurrence to.
	 *  [ "byweekno" => integer|integer list ]:
	 *       If given, it must be either an integer, or a sequence of integers,
	 *       meaning the week numbers to apply the recurrence to. Week numbers
	 *       have the meaning described in ISO8601, that is, the first week of
	 *       the year is that containing at least four days of the new year.
	 *  [ "byweekday" => integer|integer list|weekday constant list ]:
	 *       If given, it must be either an integer (0 == MO), a sequence of
	 *       integers, one of the weekday constants (MO, TU, etc), or a sequence
	 *       of these constants. When given, these variables will define the
	 *       weekdays where the recurrence will be applied. It's also possible to
	 *       use an argument n for the weekday instances, which will mean the nth
	 *       occurrence of this weekday in the period. For example, with MONTHLY,
	 *       or with YEARLY and BYMONTH, using FR(+1) in byweekday will specify the
	 *       first friday of the month where the recurrence happens. Notice that in
	 *       the RFC documentation, this is specified as BYDAY, but was renamed to
	 *       avoid the ambiguity of that keyword.
	 *   [ "byhour" => integer|integer list ]
	 *       If given, it must be either an integer, or a sequence of integers,
	 *       meaning the hours to apply the recurrence to.
	 *   [ "byminute" => integer|integer list ]
	 *       If given, it must be either an integer, or a sequence of integers,
	 *       meaning the minutes to apply the recurrence to.
	 *   [ "bysecond" => integer|integer list ]
	 *       If given, it must be either an integer, or a sequence of integers,
	 *       meaning the seconds to apply the recurrence to.
	 * @param string $frequency
	 * @param array $options
	 */
	function __construct($frequency, array $options = array()) {
		/* @var $dtstart Timestamp */
		$interval = 1;
		$dtstart = $wkst = $count = $until = $bysetpos = $bymonth = $bymonthday = $byyearday = $byweekno = $byweekday = $byhour = $byminute = $bysecond = null;
		extract($options, EXTR_IF_EXISTS);
		if (!$dtstart instanceof Timestamp) {
			$dtstart = Timestamp::now();
		} else {
			$dtstart = $dtstart->duplicate();
		}
		$this->dtstart = $dtstart->millisecond(0);
		$this->frequency = $frequency;
		$this->frequency_index = $frequency_index = avalue(self::$frequencies, $frequency, null);
		if ($frequency_index === null) {
			throw new Exception_Parameter("Invalid frequency {frequency}", compact("frequency"));
		}
		
		$this->interval = $interval;
		$this->count = $count;
		
		# Cache the original byxxx rules, if they are provided, as the _byxxx
		# attributes do not necessarily map to the inputs, and this can be
		# a problem in generating the strings. Only store things if they've
		# been supplied (the string retrieval will just use .get())
		$this->original_rule = array();
		
		if ($until instanceof Timestamp) {
			$this->until = $until;
		}
		
		if ($this->count && $this->until) {
			zesk()->deprecated("Using both 'count' and 'until' is inconsistent with RFC 2445 and has been deprecated in dateutil. Future versions will raise an error.");
		}
		
		// wkst: Starting day of the week
		if ($wkst === null) {
			$this->wkst = \zesk\Locale::first_day_of_week();
		} else if (self::_is_integer_list("wkst", $wkst, 0, 6)) {
			$this->wkst = $wkst;
		} else if ($wkst instanceof Weekday) {
			$this->wkst = $wkst->weekday;
		}
		
		// bysetpos
		if ($bysetpos === null) {
			$this->bysetpos = null;
		} else if (self::_is_integer_list("bysetpos", $bysetpos, -366, 366, true)) {
			$this->bysetpos = $bysetpos;
		}
		$this->original_rule['bysetpos'] = $bysetpos;
		
		/*
		 * Set up defaults based on frequency, and ensure our original rule doesn't include implicit values
		 */
		if ($byweekno === null && $byyearday === null && $bymonthday === null && $byweekday === null) {
			if ($frequency === self::FREQUENCY_YEARLY) {
				if ($bymonth === null) {
					$bymonth = $dtstart->month();
					$this->original_rule['bymonth'] = null;
				}
			} elseif ($frequency === self::FREQUENCY_MONTHLY) {
				$bymonthday = $dtstart->day();
				$this->original_rule['bymonthday'] = null;
			} elseif ($frequency === self::FREQUENCY_WEEKLY) {
				$byweekday = $dtstart->weekday();
				$this->original_rule['byweekday'] = null;
			}
		}
		
		// bymonth
		if ($bymonth === null) {
			$this->bymonth = null;
		} else if (self::_is_integer_list("bymonth", $bymonth, 1, 12)) {
			$this->bymonth = $bymonth;
			$this->original_rule += array(
				'bymonth' => $bymonth
			);
		}
		
		// byyearday
		if ($byyearday === null) {
			$this->byyearday = null;
		} else if (self::_is_integer_list("byyearday", $byyearday, 1, 366)) {
			$this->byyearday = $byyearday;
			$this->original_rule['byyearday'] = $byyearday;
		}
		
		// bymonthday
		if ($bymonthday === null) {
			$this->bymonthday = array();
			$this->bynmonthday = array();
		} else if (self::_is_integer_list("bymonthday", $bymonthday, -12, 12, false)) {
			$this->bymonthday = self::_integers_filter($bymonthday, true);
			$this->bynmonthday = self::_integers_filter($bymonthday, false);
			$this->original_rule += array(
				'bymonthday' => array_merge($this->bymonthday, $this->bynmonthday)
			);
		}
		
		//byweekno
		if ($byweekno === null) {
			$this->byweekno = null;
		} else if (self::_is_integer_list("byweekno", $byweekno)) {
			$this->byweekno = $byweekno;
			$this->original_rule += array(
				'byweekno' => $byweekno
			);
		}
		
		// byweekday
		if ($byweekday === null) {
			$this->byweekday = null;
			$this->bynweekday = null;
		} else if (is_integer($byweekday) || $byweekday instanceof Weekday) {
			$byweekday = array(
				$byweekday
			);
		}
		if (is_array($byweekday)) {
			$this->byweekday = array();
			$this->bynweekday = array();
			foreach ($byweekday as $wday) {
				if (is_integer($wday)) {
					$this->byweekday[] = $wday;
				} else if (($wday instanceof Weekday && !$wday->n) || $frequency_index > self::FREQUENCY_INDEX_MONTHLY) {
					$this->byweekday[] = $wday->weekday;
				} else if ($wday instanceof Weekday) {
					$this->bynweekday += array(
						$wday->weekday => $wday->n
					);
				}
			}
			$orig_byweekday = array();
			if (count($this->byweekday) === 0) {
				$this->byweekday = null;
			} else {
				sort($this->byweekday, SORT_NUMERIC);
				foreach ($this->byweekday as $wday) {
					$orig_byweekday[] = new Weekday($wday);
				}
			}
			$orig_bynweekday = array();
			if (count($this->bynweekday) === 0) {
				$this->bynweekday = null;
			} else {
				ksort($this->bynweekday, SORT_NUMERIC);
				foreach ($this->bynweekday as $wday => $n) {
					$orig_bynweekday[] = new Weekday($wday, $n);
				}
			}
			if (!isset($this->original_rule['byweekday'])) {
				$this->original_rule['byweekday'] = array_merge($orig_byweekday, $orig_bynweekday);
			}
		}
		
		// byhour 
		if ($byhour === null) {
			if ($frequency_index < self::FREQUENCY_INDEX_HOURLY) {
				$this->byhour = array(
					$dtstart->hour()
				);
			} else {
				$this->byhour = null;
			}
		} else {
			if (self::_is_integer_list("byhour", $byhour, 0, 23)) {
				if ($frequency === self::FREQUENCY_HOURLY) {
					$this->byhour = self::_construct_byset("byhour", $dtstart, $byhour, 24);
				} else {
					$this->byhour = $byhour;
				}
				$this->original_rule['byhour'] = $this->byhour;
			}
		}
		
		// byminute
		if ($byminute === null) {
			if ($frequency_index < self::FREQUENCY_INDEX_MINUTELY) {
				$this->byminute = array(
					$dtstart->minute()
				);
			} else {
				$this->byminute = null;
			}
		} else {
			if (self::_is_integer_list("byminute", $byminute, 0, 59)) {
				if ($frequency === self::FREQUENCY_MINUTELY) {
					$this->byminute = self::_construct_byset("byminute", $dtstart->minute(), $byminute, 60);
				} else {
					$this->byminute = $byminute;
				}
				$this->original_rule['byminute'] = $this->byminute;
			}
		}
		
		// bysecond
		if ($bysecond === null) {
			if ($frequency_index < self::FREQUENCY_INDEX_SECONDLY) {
				$this->bysecond = array(
					$dtstart->second()
				);
			} else {
				$this->bysecond = null;
			}
		} else {
			if (self::_is_integer_list("bysecond", $bysecond, 0, 59)) {
				if ($frequency === self::FREQUENCY_SECONDLY) {
					$this->bysecond = self::_construct_byset("bysecond", $dtstart->second(), $bysecond, 60);
				} else {
					$this->bysecond = $bysecond;
				}
				$this->original_rule['bysecond'] = $this->bysecond;
			}
		}
		
		if ($frequency_index >= self::FREQUENCY_INDEX_HOURLY) {
			$this->timeset = null;
		} else {
			$this->timeset = array();
			foreach ($this->byhour as $hour) {
				foreach ($this->byminute as $minute) {
					foreach ($this->bysecond as $second) {
						$this->timeset[] = Time::factory()->hms($hour, $minute, $second);
					}
				}
			}
		}
	}
	
	/**
	 * Output a string that would generate this RRULE if passed to rrulestr.
	 * This is mostly compatible with RFC2445, except for the
	 * dateutil-specific extension BYEASTER.
	 * 
	 * @return string
	 */
	function __toString() {
		$output = array();
		$h = $m = $s = null;
		if ($this->dtstart) {
			$output[] = $this->dtstart->format("DTSTART:{YYYY}{MM}{DD}T{hh}{mm}{ss}");
			$h = $this->dtstart->hour();
			$m = $this->dtstart->minute();
			$s = $this->dtstart->second();
		}
		$parts = array(
			'FREQ=' . $this->frequency
		);
		if ($this->interval !== 1) {
			$parts[] = "INTERVAL=" . $this->interval;
		}
		if ($this->wkst !== 1) {
			$parts[] = "WKST=" . self::list_to_string($this->wkst);
		}
		if ($this->count !== 1) {
			$parts[] = "COUNT=" . $this->count;
		}
		if ($this->until) {
			$parts[] = $this->until->format("UNTIL={YYYY}{MM}{DD}T{hh}{mm}{ss}");
		}
		$original_rule = $this->original_rule;
		if (isset($original_rule['byweekday'])) {
			$byweekday = $original_rule['byweekday'];
			$wday_strings = array();
			foreach ($byweekday as $wday) {
				/* @var $wday Weekday */
				$wday_strings[] = strval($wday);
			}
			$original_rule['byweekday'] = $wday_strings;
		}
		foreach (array(
			'BYSETPOS' => 'bysetpos',
			'BYMONTH' => 'bymonth',
			'BYMONTHDAY' => 'bymonthday',
			'BYYEARDAY' => 'byyearday',
			'BYWEEKNO' => 'byweekno',
			'BYDAY' => 'byweekday',
			'BYHOUR' => 'byhour',
			'BYMINUTE' => 'byminute',
			'BYSECOND' => 'bysecond'
		) as $name => $var) {
			if (isset($original_rule[$var])) {
				$parts[] = "$name=" . self::list_to_string($original_rule[$var]);
			}
		}
		$output[] = implode(";", $parts);
		return implode("\n", $output);
	}
	private static function list_to_string(array $list) {
		return implode(",", $list);
	}
	/**
	 * Check and convert an integer list. Only modifies $item if returns true
	 *
	 * @param string $name Name to use in errors
	 * @param mixed $item Item to check
	 * @param integer $min Inclusive minimum values allowed
	 * @param unknown $max Inclusing maximum values allowed
	 * @param string $allow_zero
	 * @param array $map
	 * @throws Exception_Parameter
	 * @throws Exception_Range
	 * @return boolean
	 */
	private static function _is_integer_list($name, &$item, $min = null, $max = null, $allow_zero = true, array $map = null) {
		if (!is_array($item)) {
			$check_items = array(
				$item
			);
		} else {
			$check_items = array_values($item);
		}
		foreach ($check_items as $index => $num) {
			if (!is_integer($num) && !is_string($num)) {
				return false;
			}
			if ($map) {
				$num = avalue($map, $num, $num);
				if (!is_integer($num)) {
					throw new Exception_Parameter("{name} item #{index} should have an integer value ({item} is {type})", array(
						"name" => $name,
						"item" => $num,
						"index" => $index + 1,
						"type" => type($item)
					));
				}
			}
			$num = intval($num);
			if ($min !== null && $num <= $min) {
				throw new Exception_Range("{name} item #{index} must not be greater than {min} ({num} passed)", array(
					"name" => $name,
					"num" => $num,
					"index" => $index + 1,
					"min" => $min
				));
			}
			if ($max !== null && $num >= $min) {
				throw new Exception_Range("{name} item #{index} must not be greater than {min} ({num} passed)", array(
					"name" => $name,
					"num" => $num,
					"index" => $index + 1,
					"min" => $min
				));
			}
			if ($allow_zero !== true && $num === 0) {
				throw new Exception_Range("{name} item #{index} does not allow a zero value", array(
					"name" => $name,
					"index" => $index + 1
				));
			}
			$check_items[$index] = $num;
		}
		sort($check_items, SORT_NUMERIC);
		$item = $check_items;
		return true;
	}
	
	/**
	 * Filter integers and only return positive or negative ones
	 * 
	 * @param array $integers
	 * @param boolean $positive
	 * @return integer[]
	 */
	private static function _integers_filter(array $integers, $positive) {
		$result = array();
		if ($positive) {
			foreach ($integers as $i) {
				if ($i > 0) {
					$result[] = $i;
				}
			}
		} else {
			foreach ($integers as $i) {
				if ($i < 0) {
					$result[] = $i;
				}
			}
		}
		return $result;
	}
	
	/**
	 *  If a `BYXXX` sequence is passed to the constructor at the same level as
	 *  `FREQ` (e.g. `FREQ=HOURLY,BYHOUR={2,4,7},INTERVAL=3`), there are some
	 *   specifications which cannot be reached given some starting conditions.
	 *
	 *   This occurs whenever the interval is not coprime with the base of a
	 *   given unit and the difference between the starting position and the
	 *   ending position is not coprime with the greatest common denominator
	 *   between the interval and the base. For example, with a FREQ of hourly
	 *   starting at 17:00 and an interval of 4, the only valid values for
	 *   BYHOUR would be {21, 1, 5, 9, 13, 17}, because 4 and 24 are not
	 *   coprime.
	 *
	 *   :param start:
	 *       Specifies the starting position.
	 *   :param byxxx:
	 *       An iterable containing the list of allowed values.
	 *   :param base:
	 *       The largest allowable value for the specified frequency (e.g.
	 *       24 hours, 60 minutes).
	 *
	 *   This does not preserve the type of the iterable, returning a set, since
	 *   the values should be unique and the order is irrelevant, this will
	 *   speed up later lookups.
	 *
	 *   In the event of an empty set, raises a :exception:`ValueError`, as this
	 *   results in an empty rrule.
	 *
	 *
	 * @param string $name Name for debugging
	 * @param integer $start
	 * @param integer|list $byxxx
	 * @param integer $base
	 */
	private static function _construct_byset($name, $start, array $byxxx, $base) {
		$result = array();
		
		if (is_integer($byxxx)) {
			$byxxx = array(
				$byxxx
			);
		}
		foreach ($byxxx as $num) {
			$i_gcd = self::gcd($this->interval, $base);
			if ($i_gcd === 1 || last(self::divmod($num - $start, $i_gcd)) === 0) {
				$result[$num] = true;
			}
		}
		if (count($result) === 0) {
			throw new Exception_Semantics("Invalid rrule {name} generates an empty set ({base})", array(
				"name" => $name,
				"base" => $base
			));
		}
		ksort($result, SORT_NUMERIC);
		return array_keys($result);
	}
	
	/**
	 * Greatest common divisor of two integers
	 * 
	 * @param integer $a
	 * @param integer $b
	 * @return integer
	 */
	private static function gcd($a, $b) {
		if ($a === $b) {
			return $a;
		} else if ($a > $b) {
			$r = $a % $b;
			if ($r === 0) {
				return $b;
			}
			return self::gcd($b, $r);
		}
		// $a < $b
		$r = $b % $a;
		if ($r == 0) {
			return $a;
		}
		return self::gcd($a, $r);
	}
	
	/**
	 * Wacky Python 2 integer division
	 * 
	 * @param integer $a
	 * @param integer $b
	 * @return integer
	 */
	public static function intdiv($a, $b) {
		return intval(floor($a / $b));
	}
	/**
	 * Returns array($dividend / $divisor, $divident % divisor)
	 * 
	 * Works for negative numbers as well.
	 * 
	 * @param integer $dividend
	 * @param integer $divisor
	 * @return array
	 */
	public static function divmod($dividend, $divisor) {
		if ($dividend === 0 || $divisor === 0) {
			return array(
				0,
				0
			);
		}
		$intdiv = self::intdiv($dividend, $divisor);
		$rem = $dividend - $divisor * $intdiv;
		return array(
			$intdiv,
			$rem
		);
	}
	/**
	 * Compute the leap-year version of an array
	 * 
	 * @param array $source
	 */
	private static function _compute_365(array $source) {
		$target = $source;
		unset($target[59]);
		return array_values($target);
	}
	
	/**
	 * Set up static variables for computation
	 */
	private static function _init() {
		if (self::$inited) {
			return;
		}
		$monthdays = array(
			1 => 31,
			2 => 29,
			3 => 31,
			4 => 30,
			5 => 31,
			6 => 30,
			7 => 31,
			8 => 31,
			9 => 30,
			10 => 31,
			11 => 30,
			12 => 31
		);
		
		$M366MASK = array();
		foreach ($monthdays as $month => $days) {
			$M366MASK = array_merge($M366MASK, array_fill(0, $days - 1, $month));
		}
		$M366MASK = array_merge($M366MASK, array_fill(0, 7, 1));
		
		$m31 = array();
		for ($i = 1; $i < 31; $i++) {
			$m31[] = $i;
		}
		$mdays = array();
		for ($i = 29; $i < 31; $i++) {
			$mdays[$i] = array_slice($m31, 0, $i);
		}
		$MDAY366MASK = array();
		foreach ($monthdays as $month => $days) {
			$MDAY366MASK = array_merge($MDAY366MASK, $mdays[$days]);
		}
		$MDAY366MASK = array_merge($MDAY366MASK, array_slice($mdays[31], 0, 7));
		
		$m31 = array();
		for ($i = -31; $i < 0; $i++) {
			$m31[] = $i;
		}
		for ($i = 29; $i < 31; $i++) {
			$mdays[$i] = array_slice($m31, -$i);
		}
		
		$NMDAY366MASK = array();
		foreach ($monthdays as $month => $days) {
			$NMDAY366MASK = array_merge($NMDAY366MASK, $mdays[$days]);
		}
		$NMDAY366MASK = array_merge($NMDAY366MASK, array_slice($mdays[31], 0, 7));
		
		self::$WDAYMASK = array();
		for ($i = 0; $i < 55; $i++) {
			self::$WDAYMASK = array_merge(self::$WDAYMASK, array(
				0,
				1,
				2,
				3,
				4,
				5,
				6
			));
		}
		
		self::$MMASK = array(
			0 => $M366MASK,
			1 => self::_compute_365($M366MASK)
		);
		self::$MDAYMASK = array(
			0 => $MDAY366MASK,
			1 => self::_compute_365($MDAY366MASK)
		);
		self::$NMDAYMASK = array(
			0 => $NMDAY366MASK,
			1 => self::_compute_365($NMDAY366MASK)
		);
		self::$dow_flip = array_flip(self::$dow);
		self::$weekdays = array();
		for ($i = 0; $i < 7; $i++) {
			self::$weekdays[$i] = new Weekday($i);
		}
	}
	public function iterate() {
		$year = $this->dtstart->year();
		$month = $this->dtstart->month();
		$day = $this->dtstart->day();
		$hour = $this->dtstart->hour();
		$minute = $this->dtstart->minute();
		$second = $this->dtstart->$second();
		$weekday = $this->dtstart->weekday();
		$yearday = $this->dtstart->yearday();
		
		$frequency = $this->frequency;
		$interval = $this->interval;
		$until = $this->until;
		
		$iterator = new Iterator($this);
		$iterator->rebuild($year, $month);
		
		getdayset = {
			YEARLY: ii.ydayset,
		MONTHLY: ii.mdayset,
		WEEKLY: ii.wdayset,
		DAILY: ii.ddayset,
		HOURLY: ii.ddayset,
		MINUTELY: ii.ddayset,
		SECONDLY:
			ii.ddayset
		}[freq]
		
		if freq < HOURLY:
		timeset = self._timeset
		else:
		gettimeset = {
			HOURLY: ii.htimeset,
		MINUTELY: ii.mtimeset,
		SECONDLY:
			ii.stimeset
		}[freq]
		if ((freq >= HOURLY and
				self._byhour and hour not in self._byhour) or
				(freq >= MINUTELY and
						self._byminute and minute not in self._byminute) or
				(freq >= SECONDLY and
						self._bysecond and second not in self._bysecond)):
						timeset = ()
						else:
						timeset = gettimeset(hour, minute, second)
		
						total = 0
						count = self._count
						while True:
						# Get dayset with the right frequency
		dayset, start, end = getdayset(year, month, day)
		
		# Do the "hard" work ;-)
		filtered = False
		for i in dayset[start:end]:
		if ((bymonth and ii.mmask[i] not in bymonth) or
				(byweekno and not ii.wnomask[i]) or
				(byweekday and ii.wdaymask[i] not in byweekday) or
				(ii.nwdaymask and not ii.nwdaymask[i]) or
				(byeaster and not ii.eastermask[i]) or
				((bymonthday or bynmonthday) and
						ii.mdaymask[i] not in bymonthday and
						ii.nmdaymask[i] not in bynmonthday) or
				(byyearday and
						((i < ii.yearlen and i+1 not in byyearday and
								-ii.yearlen+i not in byyearday) or
								(i >= ii.yearlen and i+1-ii.yearlen not in byyearday and
										-ii.nextyearlen+i-ii.yearlen not in byyearday)))):
										dayset[i] = None
										filtered = True
		
										# Output results
		if bysetpos and timeset:
		poslist = []
		for pos in bysetpos:
		if pos < 0:
		daypos, timepos = divmod(pos, len(timeset))
		else:
		daypos, timepos = divmod(pos-1, len(timeset))
		try:
		i = [x for x in dayset[start:end]
				if x is not None][daypos]
				time = timeset[timepos]
				except IndexError:
				pass
				else:
				date = datetime.date.fromordinal(ii.yearordinal+i)
				res = datetime.datetime.combine(date, time)
				if res not in poslist:
				poslist.append(res)
				poslist.sort()
				for res in poslist:
				if until and res > until:
				self._len = total
				return
				elif res >= self._dtstart:
				total += 1
				yield res
				if count:
				count -= 1
				if not count:
				self._len = total
				return
				else:
				for i in dayset[start:end]:
				if i is not None:
				date = datetime.date.fromordinal(ii.yearordinal + i)
				for time in timeset:
				res = datetime.datetime.combine(date, time)
				if until and res > until:
				self._len = total
				return
				elif res >= self._dtstart:
				total += 1
				yield res
				if count:
				count -= 1
				if not count:
				self._len = total
				return
		
				# Handle frequency and interval
		fixday = False
		if freq == YEARLY:
		year += interval
		if year > datetime.MAXYEAR:
		self._len = total
		return
		ii.rebuild(year, month)
		elif freq == MONTHLY:
		month += interval
		if month > 12:
		div, mod = divmod(month, 12)
		month = mod
		year += div
		if month == 0:
		month = 12
		year -= 1
		if year > datetime.MAXYEAR:
		self._len = total
		return
		ii.rebuild(year, month)
		elif freq == WEEKLY:
		if wkst > weekday:
		day += -(weekday+1+(6-wkst))+self._interval*7
		else:
		day += -(weekday-wkst)+self._interval*7
		weekday = wkst
		fixday = True
		elif freq == DAILY:
		day += interval
		fixday = True
		elif freq == HOURLY:
		if filtered:
		# Jump to one iteration before next day
		hour += ((23-hour)//interval)*interval
		
				if byhour:
				ndays, hour = self.__mod_distance(value=hour,
						byxxx=self._byhour,
						base=24)
				else:
				ndays, hour = divmod(hour+interval, 24)
		
				if ndays:
				day += ndays
				fixday = True
		
				timeset = gettimeset(hour, minute, second)
				elif freq == MINUTELY:
				if filtered:
				# Jump to one iteration before next day
				minute += ((1439-(hour*60+minute))//interval)*interval
		
						valid = False
						rep_rate = (24*60)
						for j in range(rep_rate // gcd(interval, rep_rate)):
								if byminute:
								nhours, minute = \
								self.__mod_distance(value=minute,
										byxxx=self._byminute,
										base=60)
								else:
								nhours, minute = divmod(minute+interval, 60)
		
								div, hour = divmod(hour+nhours, 24)
								if div:
								day += div
								fixday = True
								filtered = False
		
								if not byhour or hour in byhour:
								valid = True
								break
		
								if not valid:
								raise ValueError('Invalid combination of interval and ' +
										'byhour resulting in empty rule.')
		
								timeset = gettimeset(hour, minute, second)
								elif freq == SECONDLY:
								if filtered:
								# Jump to one iteration before next day
								second += (((86399 - (hour * 3600 + minute * 60 + second))
										// interval) * interval)
		
										rep_rate = (24 * 3600)
										valid = False
										for j in range(0, rep_rate // gcd(interval, rep_rate)):
												if bysecond:
												nminutes, second = \
												self.__mod_distance(value=second,
														byxxx=self._bysecond,
														base=60)
												else:
												nminutes, second = divmod(second+interval, 60)
		
												div, minute = divmod(minute+nminutes, 60)
												if div:
												hour += div
												div, hour = divmod(hour, 24)
												if div:
												day += div
												fixday = True
		
												if ((not byhour or hour in byhour) and
														(not byminute or minute in byminute) and
														(not bysecond or second in bysecond)):
												valid = True
												break
		
												if not valid:
												raise ValueError('Invalid combination of interval, ' +
														'byhour and byminute resulting in empty' +
														' rule.')
		
												timeset = gettimeset(hour, minute, second)
		
												if fixday and day > 28:
												daysinmonth = calendar.monthrange(year, month)[1]
												if day > daysinmonth:
												while day > daysinmonth:
												day -= daysinmonth
												month += 1
												if month == 13:
												month = 1
												year += 1
												if year > datetime.MAXYEAR:
												self._len = total
												return
												daysinmonth = calendar.monthrange(year, month)[1]
												ii.rebuild(year, month)
	}
}