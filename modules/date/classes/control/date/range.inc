<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/date/classes/control/date/range.inc $
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Sun Apr 04 21:54:40 EDT 2010 21:54:40
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Control_Date_Range extends Control {
	function __construct($options = false) {
		parent::__construct($options);
		$this->set_option("default", "day-0-0", false);
	}
	function optionTimeValue() {
		return $this->option("TimeValue", "00:00:00");
	}
	public function validate() {
		$result = $this->w_select->validate();
		
		$dateUnit = $this->value();
		
		if ($dateUnit == "custom")
			return $this->_getCustomDateTimeRange();
		if ($dateUnit == "month") {
			$this->w_month->set_option("default", date("Y-m-d", parse_time($this->option("startdatedefault"))));
			return $this->_getMonthDateTimeRange();
		}
		
		list($unit, $soff) = pair($dateUnit, "-");
		if ($soff === false) {
			$dateUnit = $this->option("Default");
			list($unit, $soff) = pair($dateUnit, "-");
			if ($soff === false) {
				$this->setError($this->column(), "Please select a date range from the menu.");
				return false;
			}
		}
		list($soff, $eoff) = pair($soff, "-");
		if ($soff === false)
			$soff = 0;
		if ($eoff === false)
			$eoff = 0;
		
		$startDate = Timestamp::now();
		$startDate->setTime(0, 0, 0);
		$endDate = clone $startDate;
		$endDate->setTime(23, 59, 59);
		
		switch ($unit) {
			case "week":
				$weekday = $startDate->getWeekday();
				$startDate->add(0, 0, -$weekday);
				$endDate->add(0, 0, 6 - $weekday);
				$startDate->add(0, 0, -7 * $soff);
				$endDate->add(0, 0, -7 * $eoff);
				break;
			case "quarter":
				$m = $startDate->getMonth();
				$m = (intval(($m - 1) / 3) * 3) + 1;
				$startDate->setMonth($m);
				$startDate->setDay(1);
				$endDate->setMonth($m);
				$endDate->setDay(1);
				$endDate->add(0, 3, -1);
				
				$startDate->add(0, -$soff * 3, 0);
				$endDate->add(0, -$eoff * 3, 0);
				break;
			case "month":
				$startDate->setDay(1);
				$endDate->setDay(1);
				$startDate->add(0, -$soff);
				$endDate->add(0, -$eoff + 1, -1);
				break;
			case "year":
				$startDate->setMonth(1);
				$startDate->setDay(1);
				$endDate->setMonth(1);
				$endDate->setDay(1);
				$endDate->add(1, 0, 0);
				$startDate->add($soff * -1);
				$endDate->add($eoff * -1);
				break;
			case "day":
			default :
				$dateUnit = "day";
				$startDate->add(0, 0, -$soff);
				$endDate->add(0, 0, -$eoff, 0);
				break;
		}
		
		$object[$this->_startColumn()] = $startDate->__toString();
		$object[$this->_endColumn()] = $endDate->__toString();
		
		$this->_setMonthDefault();
		
		return $result;
	}
	private function _startColumn() {
		return $this->option("StartDateColumn", "StartDate");
	}
	private function _endColumn() {
		return $this->option("EndDateColumn", "EndDate");
	}
	private function _setMonthDefault() {
		$object = $this->object;
		
		$start_date = $object->get($this->_startColumn(), date("Y-m-d H:i:s", time()));
		$default = date("Y-m-d", parse_time($start_date));
		//$this->w_month->set_option("Default", $default);
		$object->set($this->w_month->column(), $default);
	}
	private function _getCustomDateTimeRange(&$object) {
		//Bug 288
		//must throw an error if start date is in the future
		//Must check to see if $object->StartDate > now
		//These are now strings so also need to parse them using parseTime function
		//If start date is indeed in the future, need to return an error using
		//this->setError() and return false
		$result = $this->w_start->check($object);
		$result = $this->w_end->check($object) && $result;
		
		if (!$result)
			return $result;
		
		//start bug fix
		//Construct new DateTime instances for start, end, and now time
		//Compare start to now first then start to end
		
		//check if allow_future_start option is false (future start prohibited) and perform check if necessary
		if (!$this->option("allow_future_start", false)) {
			$dtStart = new Timestamp($object[$this->_startColumn()]);
			$dtEnd = new Timestamp($object[$this->_endColumn()]);
			
			$dtNow = Timestamp::now();
			
			//if time is in the future, add code to throw error and exit
			if ($dtNow->compare($dtStart) < 0) {
				//must set error and return false
				$this->setError($this->column(), "Please select a start date that is not in the future");
				return false;
			}
			
			//check if allow_future_end option is true (future end prohibited) and perform check if necessary
			if (!$this->option("allow_future_end", true) && $dtNow->compare($dtEnd) == -1) {
				//must set error and return false
				$this->setError($this->column(), "Please select an end date that is not in the future");
				return false;
			}
		}
		
		//Other part of Bug 288:
		//Must not allow start date to be after end date.  If this is the case, swap dates
		//and check again.
		
		if ($dtEnd->compare($dtStart) < 0) {
			//Modify HMS accordingly
			$dtStartTempTime = clone $dtStart->getTime();
			$dtStart->setTime($dtEnd->getTime());
			$dtEnd->setTime($dtStartTempTime);
			
			//reassign StartDate and EndDate Values in array
			$object[$this->_startColumn()] = $dtEnd->__toString();
			$object[$this->_endColumn()] = $dtStart->__toString();
			
			//Set Error message and return false
			$this->setError($this->column(), "Start date was after end date, and were swapped. Please check your work.");
			return false;
		}
		//End Bug Fix
		
		$this->_setMonthDefault($object);
		return $result;
	}
	private function _getMonthDateTimeRange(&$object) {
		$result = $this->w_month->check($object);
		$v = $object->get($this->w_month->column(), date('Y-m-d H:i:s', time()));
		$startDate = new Timestamp($v);
		$startDate->setDay(1);
		$startDate->setTime(0, 0, 0);
		$endDate = clone $startDate;
		$endDate->add(0, 1, 0, 0, 0, -1);
		$object[$this->_startColumn()] = $startDate->__toString();
		$object[$this->_endColumn()] = $endDate->__toString();
		return $result;
	}
	function render() {
		$name = $this->name();
		$options = array(
			"name" => $name,
			"model" => $this->object,
			"start" => $this->child($this->_startColumn()),
			"end" => $this->child($this->_endColumn()),
			"select" => $this->child($name),
			"month" => $this->child($name . '-MonthDate')
		);
		$this->response->javascript('/share/zesk/js/zesk.js', array(
			'weight' => 'first'
		));
		$t = new Template("widgets/Control_Date_Range.tpl", $options);
		return $t->render();
	}
	private function _rangeOptions() {
		$result = array();
		if ($this->option("no_name")) {
			$result[$this->option("no_value", "")] = $this->option("no_name");
		}
		$result['day-0-0'] = 'Today';
		$result['day-1-1'] = 'Yesterday';
		$result['week-0-0'] = 'This Week';
		$result['week-1-1'] = 'Last Week';
		$result['day-6-0'] = 'Last 7 Days';
		$result['month-0-0'] = 'This Month';
		$result['month-1-1'] = 'Last Month';
		$result['quarter-0-0'] = 'This Quarter';
		$result['quarter-1-1'] = 'Last Quarter';
		if ($this->option_bool("show_long_range", true)) {
			$result['day-180-0'] = 'Last 180 Days';
			$result['year-0-0'] = 'This Year';
			$result['year-1-1'] = 'Last Year';
			$result['day-365-0'] = 'Last 365 Days';
		}
		$result['month'] = '-- by Month --';
		//$result['week']			= '-- by Week --';
		$result['custom'] = '-- Custom --';
		return $result;
	}
	private function _monthOptions() {
		$d = Date::now();
		$d->addUnit("month", -24);
		for ($i = 0; $i < 25; $i++) {
			$x = $d->getMonth(true) . "/" . $d->getYear();
			$k = $d->getYear() . "-" . $d->getMonth(true) . "-01";
			$r[$k] = $x;
			$d->addUnit("month", 1);
		}
		return $r;
	}
	protected function initialize() {
		$inherited_attributes = $this->options_include("session_default;session_default_prefix");
		
		$colName = $this->column();
		
		$f = $this->widget_factory("Control_Select")
			->names($colName, $this->label())
			->control_options($this->_rangeOptions())
			->required(true);
		$f->set_option($this->options);
		$this->child($f);
		
		$f = $this->widget_factory("Control_Date")->names($this->_startColumn(), "Start")->required(true);
		$f->set_option($this->options_include("always_load"));
		$f->set_option("Default", $this->option("StartDateDefault"));
		$f->set_option("nowrap", "nowrap");
		$f->set_option($inherited_attributes);
		$this->child($f);
		
		$f = $this->widget_factory("Control_Date")->names($this->_endColumn(), "End")->required(true);
		$f->set_option($this->options_include("always_load"));
		$f->set_option("TimeValue", "23:59:59");
		$f->set_option("nowrap", "nowrap");
		$f->set_option("Default", $this->option("EndDateDefault"));
		$f->set_option($inherited_attributes);
		$this->child($f);
		
		$f = $this->widget_factory("Control_Select")
			->names("$colName-MonthDate", __("Month"))
			->control_options($this->_monthOptions())
			->required(true);
		
		$f->set_option($this->options_include("refresh;onchange;always_load"));
		$f->set_option($inherited_attributes);
		$this->child($f);
		
		parent::initialize();
	}
}
