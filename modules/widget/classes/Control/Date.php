<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:35:38 EDT 2008
 */
namespace zesk;

/**
 * Note that Control_Timestamp inherits from this, double-check nothing is Date specific
 *
 * @author kent
 * @see Control_Timestamp
 */
class Control_Date extends Control_Timestamp {
	public function time_value($set = null) {
		if ($set !== null) {
			return $this->setOption('time_value', $set);
		}
		return $this->time_control() ? $this->object->get($this->name() . '_time') : $this->option("time_value", "00:00:00");
	}

	public function load(): void {
		parent::load();
		$value = $this->value();
		if (empty($value)) {
			$this->value(null);
		} else {
			$ts = new Timestamp($value);
			$ts->set($ts->date() . " " . $this->time_value());
			$this->value(strval($ts));
		}
	}
}
