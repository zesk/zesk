<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/date/classes/control/date/range3.inc $
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Sun Apr 04 21:53:56 EDT 2010 21:53:56
 */
namespace zesk;

class Control_Date_Range3 extends Control {
	function __construct($options = false) {
		parent::__construct($options);
		$this->set_option("default", "day-0-0", false);
	}
	function optionTimeValue() {
		return $this->option("TimeValue", "00:00:00");
	}
	private function _startColumn() {
		return $this->option("StartDateColumn", "StartDate");
	}
	private function _endColumn() {
		return $this->option("EndDateColumn", "EndDate");
	}
	public function check(&$object) {
		$this->response->jquery();
		$this->response->javascript('/share/zesk/js/zesk.js', array(
			'weight' => 'first'
		));
		$this->response->javascript('/share/zesk/jquery/ui/ui.core.js');
		$this->response->javascript('/share/zesk/jquery/ui/ui.datepicker.js');
		$this->response->javascript('/share/zesk/widgets/daterange/zesk.ui.datepicker3.js');
		
		$this->response->css('/share/zesk/jquery/themes/smoothness/ui.core.css');
		$this->response->css('/share/zesk/jquery/themes/smoothness/ui.datepicker.css');
		$this->response->css('/share/zesk/jquery/themes/smoothness/ui.theme.css');
		$this->response->css('/share/zesk/widgets/daterange/zesk.ui.datepicker3.css');
		
		$start_col = $this->_startColumn();
		$end_col = $this->_endColumn();
		
		$now = new Date(gmdate('Y-m-d'));
		
		$min_date = new Date($this->option('minimum_date', $now->__toString()));
		$max_date = new Date($this->option('maximum_date', $now->__toString()));
		
		$start_date_default = new Date($this->option_date("StartDateDefault", $now->__toString()));
		$start_date_default->clamp($min_date, $max_date);
		
		$start_date_default = $this->_default_value($start_date_default->__toString(), $start_col);
		
		$end_date_default = new Date($this->option_date("EndDateDefault", $now->__toString()));
		$end_date_default->clamp($min_date, $max_date);
		
		$end_date_default = $this->_default_value($end_date_default->__toString(), $end_col);
		
		if ($this->fromDefaults($object)) {
			$object[$start_col] = $start_date_default;
			$object[$end_col] = $end_date_default;
		} else if ($this->fromRequest($object)) {
			$cols = array(
				$this->_startColumn() => $start_date_default,
				$this->_endColumn() => $end_date_default
			);
			foreach ($cols as $col => $def) {
				$val = $this->request->get($col, $def);
				$object[$col] = is_date($val) ? $val : $def;
			}
		} else if ($this->fromObject($object)) {
			$object[$start_col] = aevalue($object, $start_col, $start_date_default);
			$object[$end_col] = aevalue($object, $end_col, $end_date_default);
		}
		
		$dtStart = new Date(avalue($object, $start_col));
		$dtEnd = new Date(avalue($object, $end_col));
		
		$dtStart->clamp($min_date, $max_date);
		$dtEnd->clamp($min_date, $max_date);
		
		$object[$start_col] = $dtStart->__toString();
		$object[$end_col] = $dtEnd->__toString();
		
		if (!$this->option("allow_future_start", false)) {
			if ($dtStart->after($now)) {
				$this->setError($this->column(), "Please select a start date that is not in the future");
				return false;
			}
			if (!$this->option("allow_future_end", true) && $dtEnd->after($now)) {
				$this->setError($this->column(), "Please select an end date that is not in the future");
				return false;
			}
		}
		
		// Must not allow start date to be after end date.  If this is the case, swap dates
		// and check again.
		if ($dtEnd->compare($dtStart) < 0) {
			$object[$start_col] = $dtEnd->__toString();
			$object[$end_col] = $dtStart->__toString();
		}
		
		$this->_save_default_value($object[$start_col], $start_col);
		$this->_save_default_value($object[$end_col], $end_col);
		
		return true;
	}
	function render($object) {
		$now = new Date(gmdate('Y-m-d'));
		
		$min_date = new Date($this->option('minimum_date', gmdate('Y-m-d')));
		$max_date = new Date($this->option('maximum_date', gmdate('Y-m-d')));
		
		$start_date_default = new Date($this->option_date("StartDateDefault", $now->__toString()));
		$start_date_default->clamp($min_date, $max_date);
		
		$end_date_default = new Date($this->option_date("EndDateDefault", $now->__toString()));
		$end_date_default->clamp($min_date, $max_date);
		
		$input_name = $this->name();
		
		$start_column = $this->_startColumn();
		$end_column = $this->_endColumn();
		
		$start_date = new Date(aevalue($object, $start_column, $start_date_default->__toString()));
		$end_date = new Date(aevalue($object, $end_column, $end_date_default->__toString()));
		
		$attrs = array(
			"mandatory" => true,
			"changeMonth" => true,
			"changeYear" => true,
			"firstDay" => $this->firstDayOfWeek(),
			"dayStatus" => 'D',
			"highlightWeek" => false,
			'showOtherMonths' => false,
			'dateFormat' => 'm/d/yy'
			//		'showButtonPanel' => 'true',
		);
		
		$tpl_options = array(
			"options" => $attrs,
			'start_column' => $start_column,
			'end_column' => $end_column,
			'input_name' => $input_name,
			'language' => $this->language(),
			'first_day_of_week' => $this->firstDayOfWeek()
		);
		$t = new Template('widgets/daterange/daterange_jquery_ready.js.tpl', $tpl_options);
		$this->response->jquery($t->output());
		
		$result = "";
		$input_type = "hidden";
		
		$result .= HTML::input($input_type, $input_name . '_minDate', $min_date->__toString());
		$result .= HTML::input($input_type, $input_name . '_maxDate', $max_date->__toString());
		
		$display_format = '{M}/{D}/{YYYY}';
		
		$result .= HTML::input($input_type, $start_column, $start_date->__toString());
		$input_attrs = array(
			'type' => 'text',
			'id' => $start_column . "_display",
			'name' => $start_column . "_display",
			'value' => $start_date->format($display_format),
			'class' => 'date-input'
		);
		$result .= HTML::tag('input', $input_attrs, null);
		$result .= HTML::input($input_type, $end_column, $end_date->__toString());
		$input_attrs = array(
			'type' => 'text',
			'id' => $end_column . "_display",
			'name' => $end_column . "_display",
			'value' => $end_date->format($display_format),
			'class' => 'date-input'
		);
		$result .= '&nbsp;&mdash;&nbsp;' . HTML::tag('input', $input_attrs, null);
		
		$result = HTML::div(array(
			'id' => $input_name . "_locale_date",
			"class" => "locale-chosen-date"
		), $this->option("empty_string", "No dates selected")) . $result;
		$result .= $this->outputChoices($min_date, $max_date);
		
		return HTML::tag('div', array(
			'class' => 'datepicker'
		), $result);
	}
	private function outputChoices(Date $minDate, Date $maxDate) {
		$options = $this->_rangeOptions($minDate, $maxDate);
		$name = $this->name();
		$result = "";
		foreach ($options as $k => $text) {
			$result .= HTML::tag("li", null, HTML::tag("a", array(
				"id" => "$name-$k",
				"href" => "javascript:zesk_datepicker_update('" . $k . "', '" . $name . "')"
			), $text));
		}
		return HTML::tag("ul", array(
			"class" => "choices"
		), $result);
	}
	private function _rangeOptions(Date $minDate, Date $maxDate) {
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
		
		foreach ($result as $k => $v) {
			$result[$k] = __($v);
		}
		
		$options = array();
		foreach ($result as $k => $v) {
			$startDate = $endDate = null;
			list($startDate, $endDate) = $this->computeRangeDates($k);
			if ($startDate) {
				if ($startDate->after($maxDate))
					continue;
				if ($endDate->before($minDate))
					continue;
			}
			$options[$k] = $v;
		}
		return $options;
	}
	private function firstDayOfWeek() {
		return $this->option_integer("first_day_of_week", 0);
	}
	private function computeRangeDates($key) {
		$parts = explode("-", $key);
		if (count($parts) !== 3)
			return array(
				null,
				null
			);
		list($unit, $soff, $eoff) = $parts;
		
		$startDate = Date::now();
		$endDate = clone $startDate;
		
		switch ($unit) {
			case "week":
				/* Use cases:
				 *
				 * Sunday FDOW
				 *  S  M  T  W  T  F  S
				 * 27 28 29 30 31  1  2
				 *  3  4  5  6  7  8  9
				 * FDOW=0
				 *  0  1  2  3  4  5  6 getWeekday
				 *  0 -1 -2 -3 -4 -5 -6 RESULT
				 *
				 * Monday FDOW
				 *  M  T  W  T  F  S  S
				 * 28 29 30 31  1  2  3
				 *  4  5  6  7  8  9 10
				 * FDOW=1
				 *  1  2  3  4  5  6  0 getWeekday
				 *  0 -1 -2 -3 -4 -5 -6 RESULT
				 *
				 * Wednesday FDOW
				 *  W  T  F  S  S  M  T
				 * 30 31  1  2  3  4  5
				 *  6  7  8  9 10 11 12
				 * FDOW=3
				 *  3  4  5  6  0  1  2 getWeekday
				 *  0 -1 -2 -3 -4 -5 -6 RESULT
				 *
				 * Saturday FDOW
				 *  S  S  M  T  W  T  F
				 * 26 27 28 29 30 31  1
				 *  2  3  4  5  6  7  8
				 * FDOW=6
				 *  6  0  1  2  3  4  5 getWeekday
				 *  0 -1 -2 -3 -4 -5 -6 RESULT
				 */
				$weekday = $startDate->getWeekday() - $this->firstDayOfWeek();
				if ($weekday < 0) {
					$weekday += 7;
				}
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
				$startDate->add(0, 0, -$soff);
				$endDate->add(0, 0, -$eoff, 0);
				break;
		}
		return array(
			$startDate,
			$endDate
		);
	}
}
