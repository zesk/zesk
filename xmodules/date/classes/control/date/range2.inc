<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/date/classes/control/date/range2.inc $
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 */
namespace zesk;

class Control_Date_Range2 extends Control {
	function __construct($options = false) {
		parent::__construct($options);
		//$this->set_option("default", "day-0-0", false);
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
	public function defaults_start_end() {
		$object = $this->object;
		
		$start_col = $this->_startColumn();
		$end_col = $this->_endColumn();
		
		$now = new Date(gmdate('Y-m-d'));
		
		$min_date = new Date($this->option('minimum_date', $now->format()));
		$max_date = new Date($this->option('maximum_date', $now->format()));
		
		$start_date_default = new Date($this->option_date("StartDateDefault", $now->format()));
		$start_date_default->clamp($min_date, $max_date);
		
		$start_date_default = $this->_default_value($start_date_default->format(), $start_col);
		
		$end_date_default = new Date($this->option_date("EndDateDefault", $now->format()));
		$end_date_default->clamp($min_date, $max_date);
		
		$end_date_default = $this->_default_value($end_date_default->format(), $end_col);
		
		return array(
			$start_date_default,
			$end_date_default
		);
	}
	public function defaults() {
		$object = $this->object;
		parent::defaults();
		$start_col = $this->_startColumn();
		$end_col = $this->_endColumn();
		
		list($start_date_default, $end_date_default) = $this->defaults_start_end();
		$object->set($start_col, $start_date_default);
		$object->set($end_col, $end_date_default);
	}
	public function load() {
		$object = $this->object;
		$start_col = $this->_startColumn();
		$end_col = $this->_endColumn();
		
		list($start_date_default, $end_date_default) = $this->defaults_start_end();
		
		$cols = array(
			$this->_startColumn() => $start_date_default,
			$this->_endColumn() => $end_date_default
		);
		foreach ($cols as $col => $def) {
			$val = $this->request->get($col, $def);
			$object->set($col, is_date($val) ? $val : $def);
		}
	}
	public function validate() {
		$object = $this->object;
		$this->response->jquery();
		$this->response->javascript('/share/zesk/js/zesk.js', array(
			'weight' => 'first'
		));
		$this->response->javascript('/share/zesk/jquery/ui/ui.core.js');
		$this->response->javascript('/share/zesk/jquery/zesk.ui.datepicker.js');
		$this->response->javascript('/share/zesk/widgets/daterange/zesk.ui.datepicker.js');
		
		$this->response->css('/share/zesk/widgets/daterange/zesk.ui.datepicker.css');
		
		$start_col = $this->_startColumn();
		$end_col = $this->_endColumn();
		
		$now = new Date(gmdate('Y-m-d'));
		
		$min_date = new Date($this->option('minimum_date', $now->format()));
		$max_date = new Date($this->option('maximum_date', $now->format()));
		
		list($start_date_default, $end_date_default) = $this->defaults_start_end();
		
		$dtStart = new Date($object->get($start_col));
		$dtEnd = new Date($object->get($end_col));
		
		$dtStart->clamp($min_date, $max_date);
		$dtEnd->clamp($min_date, $max_date);
		
		$object->set($start_col, $dtStart->format());
		$object->set($end_col, $dtEnd->format());
		
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
			$dtStartTempTime = clone $dtStart->getTime();
			$dtStart->setTime($dtEnd->getTime());
			$dtEnd->setTime($dtStartTempTime);
			
			$object[$start_col] = $dtEnd->format();
			$object[$end_col] = $dtStart->format();
			
			$this->setError($this->column(), "Start date was after end date, and were swapped. Please check your work.");
			return false;
		}
		
		$this->_save_default_value($object->get($start_col), $start_col);
		$this->_save_default_value($object->get($end_col), $end_col);
		
		return true;
	}
	function render() {
		$object = $this->object;
		
		$now = new Date(gmdate('Y-m-d'));
		
		$min_date = new Date($this->option('minimum_date', gmdate('Y-m-d')));
		$max_date = new Date($this->option('maximum_date', gmdate('Y-m-d')));
		
		$start_date_default = new Date($this->option_date("StartDateDefault", $now->format()));
		$start_date_default->clamp($min_date, $max_date);
		
		$end_date_default = new Date($this->option_date("EndDateDefault", $now->format()));
		$end_date_default->clamp($min_date, $max_date);
		
		$name = $this->column();
		$input_name = $this->name();
		
		$start_column = $this->_startColumn();
		$end_column = $this->_endColumn();
		
		$app = $this->application;
		$attrs = array(
			"rangeSelect" => true,
			"mandatory" => true,
			"changeMonth" => false,
			"changeYear" => false,
			"firstDay" => $this->firstDayOfWeek(),
			"dayStatus" => 'D',
			"highlightWeek" => false,
			"selectWeek" => true,
			"prevNextAlone" => true,
			"showWeeks" => true,
			"clickMonth" => true,
			'showOtherMonths' => false,
			'prevText' => '<img src="' . $app->url('/share/zesk/widgets/daterange/prev.png') . '" alt="Past" title="Past"  width="16" height="140" />',
			'prevTextDisabled' => '<img src="' . $app->url('/share/zesk/widgets/daterange/prev-disabled.png') . '" alt="No data exists before this month" title="No data exists before this month"  width="16" height="140" />',
			'nextText' => '<img src="' . $app->url('/share/zesk/widgets/daterange/next.png') . '" alt="Present" title="Present"  width="16" height="140" />',
			'nextTextDisabled' => '<img src="' . $app->url('/share/zesk/widgets/daterange/next-disabled.png"') . ' alt="No data exists beyond this month" title="No data exists beyond this month"  width="16" height="140" />'
		);
		//'navigationAsDateFormat' => true,
		//'showStatus' => true,
		
		$tpl_options = array(
			"options" => $attrs,
			'start_column' => $start_column,
			'end_column' => $end_column,
			'input_name' => $input_name,
			'language' => $this->language(),
			'first_day_of_week' => $this->firstDayOfWeek()
		);
		$this->response->jquery(Template::instance('widgets/daterange/daterange_jquery_ready.js.tpl', $tpl_options));
		
		$result = "";
		//		$result .= HTML::tag("div", array("class" => "locale-chosen-date", "id" => $input_name . "_locale_date"), "No dates selected.");
		$result .= HTML::tag("div", array(
			"id" => $input_name,
			"class" => "zesk-ui-datepicker"
		), "");
		$input_type = "hidden";
		
		$result .= HTML::input($input_type, $input_name . '_minDate', $min_date->format());
		$result .= HTML::input($input_type, $input_name . '_maxDate', $max_date->format());
		$result .= HTML::input($input_type, $input_name . '_range', $object->get($name));
		$result .= HTML::input($input_type, $start_column, $object->get($start_column), $start_date_default->format());
		$result .= HTML::input($input_type, $end_column, $object->get($end_column), $end_date_default->format());
		
		$arrow_opts = array();
		$arrow_opts['label_tag'] = '';
		$arrow_opts['label_a_attrs'] = array(
			'id' => $input_name . "_locale_date",
			"class" => "locale-chosen-date"
		);
		$arrow_opts['contents_attrs'] = array(
			'class' => 'zesk-ui-datepicker'
		);
		$arrow_opts['url'] = $this->option("arrow_state_url", "");
		
		$result = "TODO"; // TODO
		//	$result = arrow_widget($this->option("empty_string", "No dates selected"), $result, $input_name . '_control', $this->option("arrow_state_open", true), new Options($arrow_opts));
		
		$result .= $this->outputChoices($min_date, $max_date);
		
		return HTML::tag('div', array(
			'class' => 'datepicker'
		), $result);
	}
	private function outputChoices(Date $minDate, Date $maxDate) {
		$options = $this->_rangeOptions($minDate, $maxDate);
		$name = $this->name();
		$start = $this->_startColumn();
		$end = $this->_endColumn();
		//		$result = HTML::tag("li", null, HTML::tag("a", array("onclick" => "\$('a.toggle-arrow', \$(this).parent()).click()"), HTML::tag("em", null, __('Custom'))));
		$result = "";
		foreach ($options as $k => $text) {
			$result .= HTML::tag("li", null, HTML::tag("a", array(
				"id" => "$name-$k",
				"href" => "javascript:zesk_datepicker_update('" . $k . "', '" . $name . "', '" . $start . "', '" . $end . "')"
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
				$weekday = $startDate->weekday() - $this->firstDayOfWeek();
				if ($weekday < 0) {
					$weekday += 7;
				}
				$startDate->add(0, 0, -$weekday);
				$endDate->add(0, 0, 6 - $weekday);
				$startDate->add(0, 0, -7 * $soff);
				$endDate->add(0, 0, -7 * $eoff);
				break;
			case "quarter":
				$m = $startDate->month();
				$m = (intval(($m - 1) / 3) * 3) + 1;
				$startDate->month($m);
				$startDate->day(1);
				$endDate->month($m);
				$endDate->day(1);
				$endDate->add(0, 3, -1);
				
				$startDate->add(0, -$soff * 3, 0);
				$endDate->add(0, -$eoff * 3, 0);
				break;
			case "month":
				$startDate->day(1);
				$endDate->day(1);
				$startDate->add(0, -$soff);
				$endDate->add(0, -$eoff + 1, -1);
				break;
			case "year":
				$startDate->month(1);
				$startDate->day(1);
				$endDate->month(1);
				$endDate->day(1);
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
