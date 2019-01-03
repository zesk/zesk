<?php
/**
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

// TODO: Make this like international version in share/zesk/js/zesk.js
class View_Date_Range extends View {
	private function _startColumn() {
		return $this->option("StartDateColumn", "StartDate");
	}

	private function _endColumn() {
		return $this->option("EndDateColumn", "EndDate");
	}

	public function render() {
		$object = $this->object;
		$start_col = $this->_startColumn();
		$end_col = $this->_endColumn();

		$start = $object->get($start_col, null);
		$end = $object->get($end_col, null);

		if (empty($start) || empty($end)) {
			return $this->empty_string();
		}
		if (!is_date($start) || !is_date($end)) {
			return $this->empty_string();
		}

		$start_date = new Date($start);
		$end_date = new Date($end);

		$now = new Date("now");

		$start_format = "";
		$end_format = "";
		$year_span = ($start_date->getYear() !== $end_date->getYear());
		$start_date_year = ($start_date->getYear() === $now->getYear() && !$year_span) ? "" : " {YYYY}";
		$end_date_year = ($end_date->getYear() === $now->getYear() && !$year_span) ? "" : " {YYYY}";

		if ($start_date->equals($end_date)) {
			$start_format = "{WWWW}, {MMMM} {DDD}$start_date_year";
			$end_format = "";
		} elseif (!$start_date->equalYears($end_date)) {
			$start_format = "{MMMM} {DDD}$start_date_year";
			$end_format = " - {MMMM} {DDD}$end_date_year";
			if ($start_date->getDay() == 1 && $end_date->isLastDayOfMonth()) {
				$start_format = "{MMMM}";
				$end_format = " - {MMMM}$end_date_year";
			}
		} elseif (!$start_date->equalMonths($end_date)) {
			$start_format = "{MMMM} {DDD}";
			$end_format = " - {MMMM} {DDD}$end_date_year";
		} elseif ($start_date->getDay() == 1 && $end_date->isLastDayOfMonth()) {
			$start_format = "{MMMM}$start_date_year";
			$end_format = "";
		} else {
			$start_format = "{MMMM} {DDD}";
			$end_format = " - {DDD}$end_date_year";
		}
		$result = $start_date->format($start_format) . $end_date->format($end_format);

		return $this->render_finish($result);
	}
}
