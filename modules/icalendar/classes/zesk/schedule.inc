<?php

namespace zesk;

/**
 * @see Class_Schedule
 * @author kent
 *
 * @property id $id
 * @property Account $account
 * @property integer $frequency
 * @property string $unit
 * @property integer $weekday
 * @property integer $monthday
 * @property integer $hour
 */
class Schedule extends Object {
	/**
	 * 
	 * @param Timestamp $last
	 * @param string $timezone
	 * @return Timestamp
	 */
	public function compute_next(Timestamp $last = null, $timezone = null) {
		$unit = $this->unit;
		if (!$last) {
			$last = Timestamp::now($timezone);
		} else {
			$last->time_zone($timezone);
		}
		return $this->call_hook_arguments("compute_next_$unit", array(
			$last
		), null);
	}
	private function _next_initialize(Timestamp $last) {
		$next = clone $last;
		$next->minute(0)->second(0)->millisecond(0);
		$next->hour($this->hour);
		return $next;
	}
	/**
	 * last is NULL, returns next available time after NOW
	 * last is
	 * 
	 * @param Timestamp $last
	 * @return Timestamp
	 */
	protected function hook_compute_next_day(Timestamp $last) {
		$next = $this->_next_initialize($last);
		if ($next->before($last, true)) {
			$next->add_unit("day", 1);
		}
		return $next;
	}
	protected function hook_compute_next_week(Timestamp $last) {
		$next = $this->_next_initialize($last);
		$next->weekday($this->weekday);
		if ($next->before($last, true)) {
			$next->add_unit("day", 7);
		}
		return $next;
	}
	protected function hook_compute_next_month(Timestamp $last) {
		$next = $this->_next_initialize($last);
		$next->day($this->monthday);
		if ($next->before($last, true)) {
			$next->add_unit("month", 1);
		}
		return $next;
	}
}
