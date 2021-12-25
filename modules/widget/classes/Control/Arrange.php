<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Arrange extends Control_Select {
	protected $what = [];

	private $arrange_options = null;

	private $arrange_options_dirty = true;

	protected function initialize(): void {
		$this->set_option("skip_query_condition", true);
		$this->set_option("query_column", []);
		$this->_clean();
		parent::initialize();
	}

	protected function defaults(): void {
		$this->children_defaults();
		$this->value($this->request->get($this->name()));
	}

	private function order_by($val) {
		$options = $this->control_options();
		$map = $this->arrange_map();
		return avalue($map, strval($val));
	}

	/**
	 * Array of key values which indicate the order-by clause, the key used to identify the arrange in the query string, and the "what" clause to include
	 *
	 * You can specify one, two or all three:
	 *
	 * Specify one:
	 *
	 *     order-by (column name)
	 *
	 *     request-key is a cleaned version of the order-by
	 *     order-by is specified
	 *     what is identical to order-by
	 *
	 * Specify two:
	 *
	 *     request-key|order-by (column name)
	 *
	 *     request-key is specified
	 *     order-by is specified
	 *     what is identical to order-by
	 *
	 * request-key|order-by|what
	 *
	 *     request-key is specified
	 *     order-by is specified
	 *     what is specified
	 *
	 * @param array $set
	 * @return string|Control_Arrange
	 */
	public function arrange_options(array $set = null) {
		if ($set === null) {
			return $this->arrange_options;
		}
		$this->arrange_options = $set;
		$this->arrange_options_dirty = true;
		return $this;
	}

	private function _clean(): void {
		if ($this->arrange_options_dirty) {
			$this->_fix_options();
			$this->arrange_options_dirty = false;
		}
	}

	public function control_options(array $set = null) {
		if ($set === null) {
			$this->_clean();
		}
		return parent::control_options($set);
	}

	protected function hook_query_list(Database_Query_Select $query): void {
		$val = $this->value();
		if ($val !== null) {
			$order_by = $this->order_by($val);
			if ($order_by) {
				$query->what($this->what, true);
				$query->order_by($order_by);
			}
		}
	}

	/**
	 * Set a mapping of value => sort_column
	 * @param array $set
	 * @return Control_Arrange|array
	 */
	public function arrange_map(array $set = null) {
		return $set === null ? $this->option_array('arrange_map') : $this->set_option('arrange_map', $set);
	}

	protected function _fix_options(): void {
		$options = [];
		$map = $this->arrange_map();
		foreach ($this->arrange_options as $key => $value) {
			if (array_key_exists($key, $map)) {
				$options[$key] = $value;

				continue;
			}
			[$new_key, $order_by, $what] = explode('|', $key, 3) + [
				$key,
				null,
				null,
			];
			if ($order_by === null) {
				$new_key = preg_replace('/-+/', '-', trim(preg_replace('/[^a-z]/', '-', strtolower($key)), '-'));
				$order_by = $key;
			} elseif ($what !== null) {
				$this->what["*$what"] = StringTools::unsuffix(StringTools::unsuffix($order_by, " ASC", true), " DESC", true);
				$order_by = $what;
			}
			$options[$new_key] = $value;
			$map[$new_key] = $order_by;
		}
		if ($this->required()) {
			$no_name = $this->option('noname');
			if (!$no_name) {
				$no_name = $this->option('noname-saved');
			} else {
				$this->set_option('noname-saved', $no_name);
				$this->set_option('noname', null);
			}
			if ($no_name) {
				$this->set_option('optgroup', true);
				$options = [
					$no_name => $options,
				];
			}
		}
		parent::control_options($options);
		$this->arrange_map($map);
	}
}
