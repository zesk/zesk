<?php
namespace zesk;

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/date/classes/control/date/input.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 *            Created on Tue Jul 15 16:35:02 EDT 2008
 */
class Control_Date_Input extends Control {
	function load() {
		$def = $this->value();
		$parts = getdate($def);
		$name = $this->name();
		
		$object = $this->object;
		$object->set($name . "_mm", $this->request->geti($name . "_mm", avalue($parts, "mon")), "");
		$object->set($name . "_dd", $this->request->geti($name . "_dd", avalue($parts, "mday")), "");
		$temp = $this->request->geti($name . "_yy", avalue($parts, "year"));
		if ($temp < 100) {
			$temp = $temp + 2000;
			$object->set($name . "_yy", $temp);
		}
	}
	function validate() {
		$col = $this->column();
		
		$object = $this->object;
		$name = $this->name();
		$temp = intval($object->get($name . "_mm"));
		if ($temp < 1 || $temp > 12) {
			$derr[] = __("Month must be between 1 and 12.");
		}
		$temp = intval($object->get($name . "_dd"));
		if ($temp < 1 || $temp > 31) {
			$derr[] = __("Day must be between 1 && 31.");
		}
		$temp = intval($object->get($name . "_yy"));
		if ($temp < 100) {
			$temp = $temp + 2000;
			$object[$name . "_yy"] = $temp;
		}
		if (count($derr) > 0) {
			$this->error(__("Please fix {label}: {errors}", array(
				"label" => $this->label(),
				"errors" => implode($this->option("join_errors", " "), $derr)
			)));
			return false;
		}
		
		$ddate = $object[$name . "_yy"] . "-" . str::zero_pad($object[$name . "_mm"]) . "-" . str::zero_pad($object[$name . "_dd"]) . " " . $this->option("fixed_time", "00:00:00");
		if (is_date($ddate)) {
			$this->value($object, Timestamp::factory($ddate));
			return true;
		}
		
		if ($this->required()) {
			$this->error(__("{label} must be formatted like a date, e.g. MM/DD/YYYY", array(
				"label" => $this->label()
			)));
			return false;
		}
		
		return true;
	}
	function render() {
		$object = $this->object;
		$tattr = HTML::parse_attributes("align=\"center\" class=\"tiny\"");
		
		$name = $this->name();
		
		$tr = HTML::tag("td", "", HTML::input('text', $name . "_mm", $object->get($name . "_mm", ""), 3));
		$tr .= HTML::tag("td", "", HTML::input('text', $name . "_dd", $object->get($name . "_dd", ""), 3));
		$tr .= HTML::tag("td", "", HTML::input('text', $name . "_yy", $object->get($name . "_yy", ""), 5));
		
		$trlabel = HTML::tag("td", array(
			"class" => "month label tiny"
		) + $tattr, __("Month"));
		$trlabel = HTML::tag("td", array(
			"class" => "day label tiny"
		) + $tattr, __("Day"));
		$trlabel = HTML::tag("td", array(
			"class" => "year label tiny"
		) + $tattr, __("Year"));
		
		return HTML::tag("table", "class=\"date\"", HTML::tag("tr", "", $tr) . HTML::tag("tr", "", $trlabel));
	}
}

