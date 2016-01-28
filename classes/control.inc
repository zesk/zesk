<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/control.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 *            Created on Tue Jul 15 16:39:10 EDT 2008
 */

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
	protected function hook_query(Database_Query_Select $query) {
		$debug = false;
		$columns = to_list($this->option('query_column', $this->column()));
		if (count($columns) === 0) {
			return false;
		}
		$value = $this->value();
		if ($debug) {
			log::debug("{class} hook_query {columns} {value}", array(
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
			log::warning("{class}::hook_query had columns {columns} but none are valid", array(
				"class" => get_class($this),
				"columns" => $columns
			));
			return false;
		}
		$query->where(array(
			$where
		));
		$query_condition_map = $this->option_array("query_condition_map");
		$condition = avalue($query_condition_map, strval($value));
		$column_name = $this->option('query_column_name', $this->label());
		if (!$condition) {
			$condition = __("{column_name} is {value}", compact("column_name", "value"));
		}
		if ($condition) {
			$query->condition($condition, implode(";", $columns));
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
	private static function _sanitize_strip($value, $tags) {
		if (is_string($tags)) {
			$tags = to_list($tags);
		}
		if (is_array($tags)) {
			foreach ($tags as $tag) {
				$value = html::remove_tags($tag, $value);
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
	 * Internal function to sanitize HTML-related values before storing in an object.
	 *
	 * {@inheritDoc}
	 *
	 * @see Widget::sanitize($value)
	 */
	protected function sanitize($value) {
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
