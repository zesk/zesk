<?php
declare(strict_types=1);

/**
 * Implementation of iCalendar RRULE
 *
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
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

use zesk\Time;
use zesk\Date;

class Iterator {
	/**
	 *
	 */
	private Rule $rrule;

	/**
	 *
	 */
	private int $lastyear;

	/**
	 *
	 */
	private int $lastmonth;

	/**
	 * Accessed externally?
	 */
	public int $yearlen;

	/**
	 * Accessed externally
	 */
	public int $nextyearlen;

	/**
	 *
	 */
	public int $yearordinal;

	/**
	 *
	 */
	private int $yearweekday;

	/**
	 *
	 * @var array
	 */
	public array $mmask;

	/**
	 *
	 * @var array
	 */
	private array $mrange;

	/**
	 *
	 * @var array
	 */
	public array $mdaymask;

	/**
	 *
	 * @var array
	 */
	public array $nmdaymask;

	/**
	 *
	 * @var array
	 */
	public array $wdaymask;

	/**
	 *
	 * @var array
	 */
	public array $wnomask;

	/**
	 *
	 * @var array
	 */
	public array $nwdaymask = [];

	/**
	 *
	 * @param Rule $rrule
	 */
	public function __construct(Rule $rrule) {
		$this->rrule = $rrule;
		$this->lastyear = -1;
		$this->lastmonth = -1;
	}

	/**
	 * @param int $year
	 * @param int $month
	 * @return void
	 * @throws \zesk\Exception_Range
	 */
	public function rebuild(int $year, int $month): void {
		$date = Date::instance($year, $month, 1);
		$leap_index = $date->isLeapYear() ? 1 : 0;
		$next_leap_index = $date->isLeapYear($year + 1) ? 1 : 0;
		// Every mask is 7 days longer to handle cross-year weekly periods.
		$rrule = $this->rrule;
		if ($year !== $this->lastyear) {
			$this->yearlen = 365 + $leap_index;
			$this->nextyearlen = 365 + $next_leap_index;
			$first_year_day = Date::instance($year, 1, 1);
			$this->yearordinal = $first_year_day->gregorian();
			$first_year_day_weekday = $this->yearweekday = $first_year_day->weekday();

			$this->mmask = Rule::$MMASK[$leap_index];
			$this->mdaymask = Rule::$MDAYMASK[$leap_index];
			$this->nmdaymask = Rule::$NMDAYMASK[$leap_index];
			$this->wdaymask = array_slice(Rule::$WDAYMASK, $first_year_day_weekday);
			$this->mrange = Rule::$MRANGE[$leap_index];

			if (!$rrule->byweekno) {
				$this->wnomask = [];
			} else {
				$this->wnomask = array_fill(0, $this->yearlen + 7, 0);
				$no1wkst = $firstwkst = (7 - $this->yearweekday + $rrule->wkst) % 7;
				if ($no1wkst >= 4) {
					$no1wkst = 0;
					// Number of days in the year, plus the days we got
					// from last year.
					$wyearlen = $this->yearlen + ($this->yearweekday - $rrule->wkst) % 7;
				} else {
					// Number of days in the year, minus the days we
					// left in last year.
					$wyearlen = $this->yearlen - $no1wkst;
				}
				[$div, $mod] = Rule::divmod($wyearlen, 7);
				$num_weeks = $div + Rule::intdiv($mod, 4);
				foreach ($rrule->byweekno as $n) {
					if ($n < 0) {
						$n += $num_weeks + 1;
					}
					if ($n < 0 || $n > $num_weeks) {
						continue;
					}
					if ($n > 1) {
						$i = $no1wkst + ($n - 1) * 7;
						if ($no1wkst != $firstwkst) {
							$i -= 7 - $firstwkst;
						}
					} else {
						$i = $no1wkst;
					}
					for ($j = 0; $j < 7; $j++) {
						$this->wnomask[$i] = 1;
						$i += 1;
						if ($this->wdaymask[$i] == $rrule->wkst) {
							break;
						}
					}
				}
				if (isset($rrule->byweekno[1])) {
					// Check week number 1 of next year as well
					// TODO: Check -numweeks for next year.
					$i = $no1wkst + $num_weeks * 7;
					if ($no1wkst != $firstwkst) {
						$i -= 7 - $firstwkst;
					}
					if ($i < $this->yearlen) {
						// If week starts in next year, we
						// don't care about it.
						for ($j = 0; $j < 7; $j++) {
							$this->wnomask[$i] = 1;
							$i += 1;
							if ($this->wdaymask[$i] == $rrule->wkst) {
								break;
							}
						}
					}
				}
				if ($no1wkst) {
					//                     # Check last week number of last year as
					//                     # well. If no1wkst is 0, either the year
					//                     # started on week start, or week number 1
					//                     # got days from last year, so there are no
					//                     # days from last year's last week number in
					//                     # this year.
					if (!isset($rrule->byweekno[-1])) {
						$lyd = Date::instance($year - 1, 1, 1);
						$lyearweekday = $lyd->weekday();
						$lno1wkst = (7 - $lyearweekday + $rrule->wkst) % 7;
						$lyearlen = 365 + $lyd->isLeapYear();
						if ($lno1wkst >= 4) {
							$lnumweeks = 52 + Rule::intdiv(($lyearlen + ($lyearweekday - $rrule->wkst) % 7) % 7, 4);
						} else {
							$lnumweeks = 52 + Rule::intdiv(($this->yearlen - $no1wkst) % 7, 4);
						}
					} else {
						$lnumweeks = -1;
					}
					if (isset($rrule->byweekno[$lnumweeks])) {
						for ($i = 0; $i < $no1wkst; $i++) {
							$this->wnomask[$i] = 1;
						}
					}
				}
			}
		}

		if ($rrule->bynweekday && ($month != $this->lastmonth || $year != $this->lastyear)) {
			$ranges = null;
			if ($rrule->frequency === Rule::FREQUENCY_YEARLY) {
				if ($rrule->bymonth) {
					$ranges = [];
					foreach ($rrule->bymonth as $month) {
						$ranges[] = array_slice($this->mrange, $month - 1, 2);
					}
				} else {
					$ranges = [
						[
							0,
							$this->yearlen,
						],
					];
				}
			} elseif ($rrule->frequency === Rule::FREQUENCY_MONTHLY) {
				$ranges = [
					array_slice($this->mrange, $month - 1, 2),
				];
			}

			if (is_array($ranges)) {
				// Weekly frequency won't get here, so we may not
				// care about cross-year weekly periods.
				$this->nwdaymask = array_fill(0, $this->yearlen, 0);
				foreach ($ranges as $range) {
					[$first, $last] = $range;
					$last -= 1;
					foreach ($rrule->bynweekday as $item) {
						[$first_year_day_weekday, $n] = $item;
						if ($n < 0) {
							$i = $last + ($n + 1) * 7;
							$i -= self::posmod($this->wdaymask[$i] - $first_year_day_weekday, 7);
						} else {
							$i = $first + ($n - 1) * 7;
							$i += (7 - $this->wdaymask[$i] + $first_year_day_weekday) % 7;
						}
						if (($first <= $i) || ($i <= $last)) {
							$this->nwdaymask[$i] = 1;
						}
					}
				}
			}
			$this->lastyear = $year;
			$this->lastmonth = $month;
		}
	}

	/**
	 * Python -1 % 7 = 6, not -1 as it does in PHP
	 *
	 * @param integer $number
	 * @param integer $modulo
	 * @return integer
	 */
	public static function posmod(int $number, int $modulo): int {
		$result = $number % $modulo;
		return ($result < 0) ? $result + $modulo : $result;
	}

	/**
	 * Retrieve
	 * @param int $year
	 * @param int $month
	 * @param int $day
	 * @return array
	 */
	public function getdayset(int $year, int $month, int $day): array {
		$methods = [
			'YEARLY' => fn (): array => $this->_getdayset_YEARLY($year, $month, $day),
			'MONTHLY' => fn (): array => $this->_getdayset_MONTHLY($year, $month, $day),
			'WEEKLY' => fn (): array => $this->_getdayset_WEEKLY($year, $month, $day),
			'DAILY' => fn (): array => $this->_getdayset_DAILY($year, $month, $day),
			'HOURLY' => fn (): array => $this->_getdayset_HOURLY($year, $month, $day),
			'MINUTELY' => fn (): array => $this->_getdayset_MINUTELY($year, $month, $day),
			'SECONDLY' => fn (): array => $this->_getdayset_SECONDLY($year, $month, $day),
		];
		$func = $methods[$this->rrule->frequency] ?? null;
		if ($func) {
			return $func();
		}
		$method = '_getdayset_' . $this->rrule->frequency;
		return $this->$method($year, $month, $day);
	}

	/**
	 *
	 * @param integer $hour
	 * @param integer $minute
	 * @param integer $second
	 * @return Time[]
	 */
	public function gettimeset(int $hour, int $minute, int $second): array {
		$methods = [
			'HOURLY' => fn (): array => $this->_gettimeset_HOURLY($hour, $minute, $second),
			'MINUTELY' => fn (): array => $this->_gettimeset_MINUTELY($hour, $minute, $second),
			'SECONDLY' => fn (): array => $this->_gettimeset_SECONDLY($hour, $minute, $second),
		];
		$func = $methods[$this->rrule->frequency] ?? null;
		if ($func) {
			return $func();
		}
		$method = '_gettimeset_' . $this->rrule->frequency;
		return $this->$method($hour, $minute, $second);
	}

	private function _getdayset_YEARLY(int $year, int $month, int $day): array {
		return [
			Rule::range($this->yearlen),
			0,
			$this->yearlen,
		];
	}

	private function _getdayset_MONTHLY(int $year, int $month, int $day): array {
		$dset = array_fill(0, $this->yearlen, null);
		[$start, $end] = array_slice($this->mrange, $month - 1, 2);
		for ($i = $start; $i < $end; $i++) {
			$dset[$i] = $i;
		}
		return [
			$dset,
			$start,
			$end,
		];
	}

	private function _getdayset_WEEKLY(int $year, int $month, int $day): array {
		// We need to handle cross-year weeks here.
		$dset = array_fill(0, $this->yearlen + 7, null);
		$i = Date::instance($year, $month, $day)->gregorian() - $this->yearordinal;
		$start = $i;
		for ($j = 0; $j < 7; $j++) {
			$dset[$i] = $i;
			$i += 1;
			// if (not (0 <= i < $this->yearlen) or
			//    $this->wdaymask[i] == $this->rrule._wkst):
			// This will cross the year boundary, if necessary.
			if ($this->wdaymask[$i] == $this->rrule->wkst) {
				break;
			}
		}
		return [
			$dset,
			$start,
			$i,
		];
	}

	private function _getdayset_DAILY(int $year, int $month, int $day): array {
		$dset = array_fill(0, $this->yearlen + 7, null);
		$i = Date::instance($year, $month, $day)->gregorian() - $this->yearordinal;
		$dset[$i] = $i;
		return [
			$dset,
			$i,
			$i + 1,
		];
	}

	private function _getdayset_HOURLY(int $year, int $month, int $day): array {
		return $this->_getdayset_DAILY($year, $month, $day);
	}

	private function _getdayset_MINUTELY(int $year, int $month, int $day): array {
		return $this->_getdayset_DAILY($year, $month, $day);
	}

	private function _getdayset_SECONDLY(int $year, int $month, int $day): array {
		return $this->_getdayset_DAILY($year, $month, $day);
	}

	/**
	 * @param int $hour
	 * @param int $minute
	 * @param int $second
	 * @return Time[]
	 */
	private function _gettimeset_HOURLY(int $hour, int $minute, int $second): array {
		$tset = [];
		$rrule = $this->rrule;
		foreach ($rrule->byminute as $minute) {
			foreach ($rrule->bysecond as $second) {
				$tset[] = Time::instance($hour, $minute, $second);
			}
		}
		usort($tset, 'zesk\\Time::sort_callback');
		return $tset;
	}

	/**
	 * @param int $hour
	 * @param int $minute
	 * @param int $second
	 * @return Time[]
	 */
	private function _gettimeset_MINUTELY(int $hour, int $minute, int $second): array {
		$tset = [];
		$rrule = $this->rrule;
		foreach ($rrule->bysecond as $second) {
			$tset[] = Time::instance($hour, $minute, $second);
		}
		usort($tset, 'zesk\\Time::sort_callback');
		return $tset;
	}

	/**
	 * @param int $hour
	 * @param int $minute
	 * @param int $second
	 * @return Time[]
	 */
	private function _gettimeset_SECONDLY(int $hour, int $minute, int $second) {
		return [
			Time::instance($hour, $minute, $second),
		];
	}
}
