<?php
/**
 * HTML Select Widget
 *
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 */

/**
 * HTML Select Widget
 *
 * @see Control_Options
 * @author kent
 *
 */
class Control_Select extends Control_Options {
	const default_escape_values = true;

	protected function initialize() {
		parent::initialize();
		if ($this->control_options === null) {
			$this->control_options = $this->option_array("options", array());
		}
		$options = $this->control_options;
		$preferred = $this->option_array('preferred_keys');
		if (count($preferred)) {
			$preferred_options = array();
			foreach ($preferred as $key) {
				if (array_key_exists($key, $options)) {
					$preferred_options[$key] = $options[$key];
					if ($this->option_bool('preferred_keys_move', false)) {
						unset($options[$key]);
					}
				}
			}
			if (count($preferred_options) > 0) {
				$preferred_title = $this->option('preferred_title', __('Preferred'));
				$unpreferred_title = $this->option('unpreferred_title', __('Other'));
				$this->control_options = array(
					$preferred_title => $preferred_options,
					$unpreferred_title => $options
				);
				$this->set_option("optgroup", true);
			}
		}
	}
	static function _control_options_count(array $arr) {
		$n = count($arr);
		foreach ($arr as $k => $v) {
			if (is_array($v)) {
				$n += self::_control_options_count($v);
			}
		}
		return $n;
	}

	function control_options_count() {
		return self::_control_options_count($this->control_options);
	}

// 	public function submitted() {
// 		$name = $this->name();
// 		if ($name && $this->request->has($name)) {
// 			return true;
// 		}
// 		return false;
// 	}

	function is_visible() {
		if (!parent::is_visible()) {
			return false;
		}
		$noname = $this->noname();
		$n_options = $this->control_options_count();
		if (!empty($noname)) {
			$n_options += 1;
		}
		if ($this->hide_single() && $n_options <= 1) {
			return false;
		}
		return true;
	}
	function hide_single($set = null) {
		return ($set !== null) ? $this->set_option('hide_single', to_bool($set)) : $this->option_bool('hide_single', true);
	}
	function hide_single_text($set = null) {
		return ($set !== null) ? $this->set_option('hide_single_text', to_bool($set)) : $this->option_bool('hide_single_text');
	}
	function single_tag($set = null) {
		return ($set !== null) ? $this->set_option('single_tag', $set) : $this->option('single_tag');
	}
	function single_tag_attributes(array $set = null) {
		return ($set !== null) ? $this->set_option('single_tag_attributes', $set) : $this->option_array('single_tag_attributes');
	}
	function validate() {
		if (to_bool(avalue($this->options, 'disabled'))) {
			return true;
		}
		// If nothing was submitted, then we are still valid.
		$name = $this->name();
		if (!$this->request->has($name)) {
			return true;
		}
		$value = $this->value();
		if ($this->option("refresh", false)) {
			$continue = $this->name() . '_sv';
			if ($this->request->getb($continue)) {
				$this->message($this->option("refresh_message", __("Form has been updated, check your settings.")));
				return false;
			}
		}
		if (!$this->has_control_option($value)) {
			$this->error_required();
			return false;
		}
		return $this->validate_required();
	}

	protected function hook_query(Database_Query_Select $query) {
		if (!parent::hook_query($query)) {
			return false;
		}
		if (!$this->has_option("query_condition_map")) {
			$value = $this->value();
			$text_value = avalue($this->control_options, strval($value));
			if ($text_value) {
				$condition = __("{label} is {text_value}", array(
					"label" => $this->label(),
					"text_value" => $text_value
				));
				// Overwrite default condition set by parent
				$query->condition($condition, $this->column());
			}
		}
		return true;
	}

	public function escape_values($set = null) {
		return $set !== null ? $this->set_option('escape_values', to_bool($set)) : $this->option_bool('escape_values', self::default_escape_values);
	}

	public function theme_variables() {
		return array(
			'escape_values' => $this->escape_values()
		) + parent::theme_variables();
	}
}
