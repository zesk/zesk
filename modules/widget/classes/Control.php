<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2017, Market Acumen, Inc.
 */
namespace zesk;

/**
 * Controls edit model state when submitted
 *
 * @author kent
 * @see Widget
 */
class Control extends Widget {

	/**
	 *
	 * @var array
	 */
	static $default_strip_tags = array(
		'script',
		'link'
	);
	/**
	 *
	 * @var array
	 */
	static $default_allow_tags = array(
		'a',
		'b',
		'blockquote',
		'br',
		'code',
		'dd',
		'del',
		'div',
		'dl',
		'dt',
		'em',
		'h1',
		'h2',
		'h3',
		'h4',
		'h5',
		'h6',
		'hr',
		'i',
		'img',
		'kbd',
		'li',
		'ol',
		'p',
		'pre',
		's',
		'span',
		'strike',
		'strong',
		'sub',
		'sup',
		'table',
		'tbody',
		'td',
		'thead',
		'tr',
		'ul'
	);

	/**
	 * Retrieve the key used to assign this condition to allow overwriting by subclasses
	 *
	 * @param Database_Query_Select $query
	 * @return NULL|string
	 */
	protected function query_condition_key() {
		$columns = $this->query_columns();
		if (!$columns) {
			return null;
		}
		return implode(";", $columns);
	}

	/**
	 * Retrieves a mapping of value to "condition" used to describe when this filter has matched
	 *
	 * e.g. If the values are "on", "off", and "snooze" a valid query_condition_map is:
	 *
	 * [ "on" => $locale->__("is on"), "off" => $locale->__("is off"), "snooze" => $locale->__("is snoozed") ]
	 *
	 * Note for
	 *
	 * @param array $set
	 * @return self|array
	 */
	protected function query_condition_map(array $set = null) {
		if (is_array($set)) {
			$this->set_option("query_condition_map", $set);
			return $this;
		}
		return $this->option_array("query_condition_map");
	}
	/**
	 * Retrieve the key used to assign this condition to allow overwriting by subclasses
	 *
	 * @param Database_Query_Select $query
	 * @return NULL|string
	 */
	protected function query_columns() {
		$columns = to_list($this->option('query_column', $this->column()));
		if (count($columns) === 0) {
			return null;
		}
		return $columns;
	}
	protected function hook_query(Database_Query_Select $query) {
		$debug = false;
		$columns = $this->query_columns();
		if (!$columns) {
			return false;
		}
		$value = $this->value();
		if ($debug) {
			$this->application->logger->debug("{class} hook_query {columns} {value}", array(
				"class" => get_class($this),
				"columns" => $columns,
				"value" => $value
			));
		}
		if ($value === null || $value === '') {
			return false;
		}
		$where = array();
		foreach ($columns as $column) {
			if (!$query->valid_column($column)) {
				continue;
			}
			$where[$column] = $value;
		}
		if (count($where) === 0) {
			$this->application->logger->warning("{class}::hook_query had columns {columns} but none are valid", array(
				"class" => get_class($this),
				"columns" => $columns
			));
			return false;
		}
		$query->where(array(
			$where
		));
		if ($this->option_bool('skip_query_condition')) {
			return false;
		}
		$query_condition_map = $this->option_array("query_condition_map");
		$condition = avalue($query_condition_map, strval($value));
		$column_name = $this->option('query_column_name', $this->label());
		if (!$condition) {
			$condition = __("{column_name} is {value}", compact("column_name", "value"));
		}
		if ($condition) {
			$query->condition($condition, $this->query_condition_key());
		}
		return true;
	}
	final function refresh($set = null) {
		return ($set !== null) ? $this->set_option('refresh', to_bool($set)) : $this->option_bool('refresh');
	}
	public function placeholder($set = null) {
		return ($set !== null) ? $this->set_option('placeholder', $set) : $this->option('placeholder');
	}
	public function query_column($set = null) {
		return ($set !== null) ? $this->set_option('query_column', $set) : $this->option('query_column');
	}

	/**
	 * Enable sanitization of inputs to remove dangerous HTML
	 *
	 * Sanitization occurs by:
	 *
	 * 1. Stripping bad HTML tags from inputs
	 * 2. Allowing good HTML tags in inputs
	 *
	 * Use boolean values to strip/allow all tags
	 *
	 * @param boolean $set
	 * @return boolean|Widget
	 */
	protected function sanitize_html($set = null) {
		return $set === null ? $this->option_bool('sanitize_html', true) : $this->set_option('sanitize_html', to_bool($set));
	}

	/**
	 * Just strip dangerous tags (script, link)
	 */
	protected function sanitize_strip_default_tags() {
		return $this->sanitize_html(true)->sanitize_allow_tags(false)->sanitize_strip_tags(self::$default_strip_tags);
	}

	/**
	 * Just strip dangerous tags (script, link)
	 */
	protected function sanitize_strip_all_tags() {
		return $this->sanitize_html(true)->sanitize_allow_tags(false)->sanitize_strip_tags(true);
	}

	/**
	 * Only allow default tags (markup, safe)
	 */
	protected function sanitize_allow_default_tags() {
		return $this->sanitize_html(true)->sanitize_allow_tags(self::$default_allow_tags)->sanitize_strip_tags(false);
	}

	/**
	 * Allow all tags (whoo-hoo!)
	 */
	protected function sanitize_allow_all_tags() {
		return $this->sanitize_html(false);
	}

	/**
	 * Get/set specific tags to strip
	 *
	 * true means strip ALL HTML tags from all input
	 * false means strip nothing from all input
	 * string|array is list of HTML tags to strip
	 *
	 * @see strip_tags
	 * @param mixed $set
	 * @return Widget|mixed
	 */
	protected function sanitize_strip_tags($set = null) {
		return $set === null ? $this->option('sanitize_strip_tags', true) : $this->set_option('sanitize_strip_tags', $set);
	}

	/**
	 * Get/set specific tags to strip
	 *
	 * true means allow ALL HTML tags from all input (except strip tags)
	 * false means strip nothing from all input (skips this step)
	 * string|array is list of HTML tags to strip
	 *
	 * @see strip_tags
	 * @param mixed $set
	 * @return Widget|mixed
	 */
	protected function sanitize_allow_tags($set = null) {
		return $set === null ? $this->option('sanitize_allow_tags', self::$default_allow_tags) : $this->set_option('sanitize_allow_tags', $set);
	}

	/**
	 *
	 * @param string $value
	 * @param list $tags
	 * @return string
	 */
	private static function _sanitize_strip($value, $tags) {
		if (is_string($tags)) {
			$tags = to_list($tags);
		}
		if (is_array($tags)) {
			foreach ($tags as $tag) {
				$value = HTML::remove_tags($tag, $value);
			}
		}
		return $value;
	}

	/**
	 * Sanitize a non-iterable value
	 *
	 * @param unknown $value
	 */
	private function _sanitize($value) {
		if (is_object($value) || $value === null || is_bool($value) || is_numeric($value)) {
			return $value;
		}
		$allow = $this->sanitize_allow_tags();
		$strip = $this->sanitize_strip_tags();
		if ($strip === true) {
			return strip_tags($value);
		}
		if (is_string($strip)) {
			$value = self::_sanitize_strip(to_list($value), $strip);
		} else if (is_array($strip)) {
			$value = self::_sanitize_strip($value, $strip);
		}
		if ($allow === true) {
			return $value;
		}
		if (is_string($allow)) {
			return strip_tags($value, implode("", arr::wrap(to_list($allow), '<', '>')));
		}
		if (is_array($allow)) {
			return strip_tags($value, implode("", arr::wrap($allow, '<', '>')));
		}
		return $value;
	}

	/**
	 * Public function to sanitize HTML-related values before storing in an object.
	 *
	 * {@inheritDoc}
	 *
	 * @see Widget::sanitize($value)
	 */
	public function sanitize($value) {
		if (!$this->sanitize_html()) {
			return $value;
		}
		if (can_iterate($value)) {
			foreach ($value as $k => $v) {
				$value[$k] = $this->_sanitize($v);
			}
			return $value;
		}
		return $this->_sanitize($value);
	}
}
