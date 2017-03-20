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
use zesk\Exception_System;

/**
 * The rruleset type allows more complex recurrence setups, mixing
 * multiple rules, dates, exclusion rules, and exclusion dates. 
 */
class Set {
	
	/**
	 * @var boolean
	 */
	private $dirty = null;
	
	/**
	 * @var Rule[]
	 */
	private $rrule = array();
	
	/**
	 * @var Timestamp[]
	 */
	private $rdate = array();
	
	/**
	 * @var Rule[]
	 */
	private $exrule = array();
	
	/**
	 * @var Timestamp[]
	 */
	private $exdate = array();
	
	/**
	 * 
	 * @param Rule $rrule
	 * @return \RRule\Set
	 */
	public function rrule(Rule $rrule) {
		$this->rrule[] = $rrule;
		$this->dirty = true;
		return $this;
	}
	
	/**
	 * 
	 * @param Timestamp $rdate
	 * @return \RRule\Set
	 */
	public function rdate(Timestamp $rdate) {
		$this->rdate[] = $rdate;
		$this->dirty = true;
		return $this;
	}
	
	/**
	 * 
	 * @param Rule $exrule
	 * @return \RRule\Set
	 */
	public function exrule(Rule $exrule) {
		$this->exrule[] = $exrule;
		$this->dirty = true;
		return $this;
	}
	
	/**
	 * 
	 * @param Timestamp $exdate
	 * @return \RRule\Set
	 */
	public function exdate(Timestamp $exdate) {
		$this->exdate[] = $exdate;
		$this->dirty = true;
		return $this;
	}
	
	/**
	 * 
	 * @return Generator
	 */
	public function iterator() {
		$rlist = array();
		if ($this->dirty) {
			usort($this->rdate, "zesk\\Timestamp::compare_callback");
			usort($this->exdate, "zesk\\Timestamp::compare_callback");
		}
		
		$rules = new Rules($this->rrule, $this->rdate);
		$exrules = new Rules($this->exrule, $this->exdate);
		
		$total = 0;
		$exrules->rewind();
		$extimestamp = null;
		foreach ($rules as $timestamp) {
			while ($exrules->valid() && ($extimestamp = $exrules->current()) && $extimestamp->before($timestamp)) {
				$exrules->next();
			}
			if ($extimestamp instanceof Timestamp) {
				if ($timestamp->equals($extimestamp)) {
					continue;
				}
			} else if ($extimestamp !== null) {
				throw new Exception_System("ExTimestamp invalid value \"{value}\" type({type})", array(
					"value" => $extimestamp,
					"type" => type($extimestamp)
				));
			}
			yield $timestamp;
		}
	}
}