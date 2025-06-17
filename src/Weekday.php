<?php

namespace zesk;

abstract class Weekday extends Temporal {
	
	/**
	 * Get day of the week
	 *
	 * @return integer
	 */
	abstract public function weekday(): int;
	
	/**
	 * Sets the DOW to the selected one
	 * Weekday, when set, is always the NEXT possible weekday, including today.
	 *
	 * @param int $set
	 * @return $this
	 */
	public function setWeekday(int $set): self
	{
		$set = abs($set) % 7;
		$weekday = $this->weekday();
		if ($weekday === $set) {
			return $this;
		}
		if ($weekday < $set) {
			$dd = $set - $weekday;
		} else {
			$dd = 7 + $set - $weekday;
		}
		return $this->add(0, 0, $dd);
	}
	
	
	/**
	 * Set to the past weekday specified
	 *
	 * @param int $set
	 * @return Timestamp
	 */
	public function setWeekdayPast(int $set): self
	{
		return $this->setWeekday($set)->addUnit(-7, Temporal::UNIT_DAY);
	}
	
}
