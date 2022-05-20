<?php declare(strict_types=1);
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

use zesk\Timestamp;
use \Iterator;

class Rules implements Iterator {
	/**
	 * @var Rule[]
	 */
	private $rules = [];

	/**
	 * @var Timestamp[]
	 */
	private $timestamps = [];

	/**
	 * @var \Generator
	 */
	private $generator = [];

	/**
	 * @var zesk\Timestamp
	 */
	private $current = [];

	/**
	 * Construct a group of rules which includes multiple RRule\Rules and Timestamps
	 *
	 * @param array $rules
	 * @param array $timestamps
	 */
	public function __construct(array $rules, array $timestamps) {
		$this->rules = array_values($rules);
		$this->timestamps = array_values($timestamps);
	}

	/**
	 * Return the topmost item in
	 * {@inheritDoc}
	 * @see Iterator::current()
	 */
	public function current(): mixed {
		foreach ($this->current as $index => $timestamp) {
			return $timestamp;
		}
		return null;
	}

	public function key(): mixed {
		foreach ($this->current as $index => $timestamp) {
			return $timestamp->__toString();
		}
		return null;
	}

	public function next(): void {
		foreach ($this->current as $index => $timestamp) {
			if ($index < 0) {
				unset($this->current[$index]);
			} else {
				$this->generator[$index]->next();
				$this->current[$index] = $this->generator[$index]->current();
				if (empty($this->current[$index])) {
					unset($this->current[$index]);
				}
			}
			$this->current_sort();
			return;
		}
	}

	public function rewind(): void {
		$this->current = [];
		foreach ($this->rules as $index => $rule) {
			$this->generator[$index] = $rule->generator();
			$this->generator[$index]->rewind();
			$this->current[$index] = $this->generator[$index]->current();
			if (!$this->current[$index]) {
				unset($this->current[$index]);
			}
		}
		foreach ($this->timestamps as $index => $timestamp) {
			$this->current[-($index + 1)] = $timestamp;
		}
		$this->current_sort();
	}

	/**
	 * @return boolean
	 */
	public function valid(): bool {
		return count($this->current) > 0;
	}

	private function current_sort(): void {
		uasort($this->current, 'zesk\\Timestamp::compare_callback');
	}
}
