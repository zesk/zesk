<?php declare(strict_types=1);
/**
 * HTML Select Widget
 *
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

/**
 * HTML Select Widget
 *
 * @see Control_Options
 * @author kent
 *
 */
class Control_Select extends Control_Optionss {
	/**
	 *
	 * @var boolean
	 */
	public const default_escape_values = true;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Control_Options::initialize()
	 */
	protected function initialize(): void {
		parent::initialize();
		if ($this->control_options === null) {
			$this->control_options = $this->optionArray('options', []);
		}
		$options = $this->control_options;
		$preferred = $this->optionArray('preferred_keys');
		if (count($preferred)) {
			$preferred_options = [];
			foreach ($preferred as $key) {
				if (array_key_exists($key, $options)) {
					$preferred_options[$key] = $options[$key];
					if ($this->optionBool('preferred_keys_move', false)) {
						unset($options[$key]);
					}
				}
			}
			if (count($preferred_options) > 0) {
				$preferred_title = $this->option('preferred_title', __('Preferred'));
				$unpreferred_title = $this->option('unpreferred_title', __('Other'));
				$this->control_options = [
					$preferred_title => $preferred_options,
					$unpreferred_title => $options,
				];
				$this->setOption('optgroup', true);
			}
		}
	}

	/**
	 *
	 * @param array $arr
	 * @return number
	 */
	public static function _control_options_count(array $arr) {
		$n = count($arr);
		foreach ($arr as $k => $v) {
			if (is_array($v)) {
				$n += self::_control_options_count($v);
			}
		}
		return $n;
	}

	/**
	 *
	 * @return integer
	 */
	public function control_options_count() {
		return self::_control_options_count($this->control_options);
	}

	// 	public function submitted() {
	// 		$name = $this->name();
	// 		if ($name && $this->request->has($name)) {
	// 			return true;
	// 		}
	// 		return false;
	// 	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::is_visible()
	 * @return boolean
	 */
	public function is_visible() {
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

	/**
	 * Getter/setter for multiple selection
	 *
	 * @param boolean|null $set
	 * @return self|boolean
	 */
	public function multiple($set = null) {
		return ($set !== null) ? $this->setOption('multiple', toBool($set)) : $this->optionBool('multiple', false);
	}

	/**
	 * Set to TRUE to force single values to be hidden or displayed using an alternate output
	 *
	 * @param unknown $set
	 * @return void|mixed|boolean
	 */
	public function hide_single($set = null) {
		return ($set !== null) ? $this->setOption('hide_single', toBool($set)) : $this->optionBool('hide_single', true);
	}

	/**
	 * Getter/setter. When set to true, outputs hidden input when single option exists.
	 *
	 * @param boolean $set
	 * @return boolean|self
	 */
	public function hide_single_text($set = null) {
		return ($set !== null) ? $this->setOption('hide_single_text', toBool($set)) : $this->optionBool('hide_single_text');
	}

	/**
	 *
	 * @return boolean
	 */
	public function is_single() {
		$optgroup = $this->optionBool('optgroup');
		return $this->option('hide_single', $this->required()) && (count($this->control_options) === 1 && $optgroup === false);
	}

	/**
	 * Getter/setter the single_tag attribute - The HTML tag used to delimit a selection list with one item, instead of the usual `select` tag.
	 *
	 * You can set attributes for this tag using self::single_tag_attributes($attributes);
	 *
	 * Only used when a single item would output, and "hide_single" is active, or the item is required, or
	 *
	 * @param string|false $set HTML Tag or false to dis
	 * @return void|mixed|string|array
	 */
	public function single_tag($set = null) {
		return ($set !== null) ? $this->setOption('single_tag', $set) : $this->option('single_tag');
	}

	public function single_tag_attributes(array $set = null) {
		return ($set !== null) ? $this->setOption('single_tag_attributes', $set) : $this->optionArray('single_tag_attributes');
	}

	public function validate(): bool {
		if (toBool($this->options['disabled'] ?? null)) {
			return true;
		}
		// If nothing was submitted, then we are still valid.
		$name = $this->name();
		if (!$this->request->has($name)) {
			return true;
		}
		$value = $this->value();
		if ($this->option('refresh', false)) {
			$continue = $this->name() . '_sv';
			if ($this->request->getBool($continue)) {
				$this->message($this->option('refresh_message', __('Form has been updated, check your settings.')));
				return false;
			}
		}
		if ($this->multiple()) {
			foreach ($value as $val) {
				if ($this->has_control_option($val)) {
					return true;
				}
			}
			$this->error_required();
			return false;
		} elseif (!$this->has_control_option($value)) {
			$this->error_required();
			return false;
		}
		return $this->validate_required();
	}

	protected function value_to_text() {
		$value = $this->value();
		if ($this->multiple()) {
			$text_values = [];
			foreach ($value as $val) {
				$text_values[] = $text_value = $this->control_options[strval($val)] ?? null;
			}
			return implode(', ', $text_values);
		}
		$key = strval($value);
		if ($this->optionBool('optgroup')) {
			foreach ($this->control_options as $k => $options) {
				if (array_key_exists($key, $options)) {
					return $options[$key];
				}
			}
			return null;
		}
		$text_value = $this->control_options[$key] ?? null;
		return $text_value;
	}

	protected function hook_query(Database_Query_Select $query) {
		parent::hook_query($query);
		if ($this->optionBool('skip_query_condition')) {
			return false;
		}
		if (!$this->hasOption('query_condition_map')) {
			$text_value = $this->value_to_text();
			if ($text_value) {
				$condition = __('{label} is {text_value}', [
					'label' => $this->label(),
					'text_value' => $text_value,
				]);
				// Overwrite default condition set by parent
				$query->condition($condition, $this->query_condition_key());
			}
		}
		return true;
	}

	public function escape_values($set = null) {
		return $set !== null ? $this->setOption('escape_values', toBool($set)) : $this->optionBool('escape_values', self::default_escape_values);
	}

	public function themeVariables(): array {
		return [
			'escape_values' => $this->escape_values(),
			'multiple' => $this->multiple(),
		] + parent::themeVariables();
	}
}
