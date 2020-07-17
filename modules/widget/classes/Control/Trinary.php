<?php
namespace zesk;

class Control_Trinary extends Control_Select {
	protected function initialize() {
		parent::initialize();
		$this->control_options($this->application->locale->__(array(
			'null' => 'Unanswered',
			0 => 'No',
			1 => 'Yes',
		)));
	}

	protected function hook_query(Database_Query_Select $query) {
		$val = $this->value();
		$column = $this->query_column();
		$locale = $this->application->locale;
		if ($val === "null") {
			$query->condition($locale->__("have not answered {label}", array(
				"label" => $this->label,
			)), $this->query_condition_key());
			$query->where($column, null);
		} elseif (is_numeric($val)) {
			$query->condition($locale->__("answered {value} for {label}", array(
				"label" => $this->label,
				"value" => $val ? $locale->__("yes") : $locale->__("no"),
			)), $this->query_condition_key());
			$query->where($column, $val);
		}
	}
}
