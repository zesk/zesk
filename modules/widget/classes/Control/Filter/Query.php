<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Control_Filter_Query extends Control_Select {
	protected $options = array(
		'refresh' => true
	);
	protected $query_options = array();
	public function query_options(array $set = null, $add = true) {
		if ($set === null) {
			return $this->query_options;
		}
		if (!$add) {
			$this->query_options = array();
			$this->control_options = array();
		}
		foreach ($set as $code => $options) {
			$title = avalue($options, 'title', $code);
			$this->control_options[$code] = $title;
			$this->query_options[$code] = $options;
		}
		return $this;
	}
	protected function hook_query(Database_Query_Select $query) {
		$value = $this->value();
		if (array_key_exists($value, $this->query_options)) {
			foreach ($this->query_options[$value] as $name => $query_value) {
				$method = "_filter_$name";
				if (method_exists($this, $method)) {
					$this->$method($query, $query_value);
				}
			}
		}
	}
	protected function filter_map() {
		$value = $this->value();
		return $this->option_array("filter_map") + array(
			'query_column' => $this->query_column(),
			'name' => $this->name(),
			'id' => $this->id(),
			'value' => $value,
			'text_value' => avalue($this->control_options, strval($value), $value),
			'label' => $this->label(),
			'column' => $this->column()
		);
	}
	protected function _filter_where(Database_Query_Select $query, $value) {
		$map = $this->filter_map();
		$value = arr::scalars($value);
		$query->where(amap($value, $map));
	}
	protected function _filter_condition(Database_Query_Select $query, $value) {
		$query->condition(map($value, $this->filter_map()), $this->query_condition_key());
	}
	protected function _filter_what(Database_Query_Select $query, $value) {
		$map = $this->filter_map();
		$query->what(amap($value, $map));
	}
	protected function _filter_order_by(Database_Query_Select $query, $value) {
		$map = $this->filter_map();
		$query->order_by(amap($value, $map));
	}
	protected function _filter_join(Database_Query_Select $query, $value) {
		$map = $this->filter_map();
		$query->join(amap($value, $map));
	}
	protected function _filter_link(Database_Query_Select $query, $value) {
		list($class, $mixed) = $value;
		$query->link($class, $mixed);
	}
}
