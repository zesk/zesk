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
class Control_Text_Dropdown extends Control_Text {
	public const option_button_label = 'button_label';

	public const option_select_behavior_enabled = 'select_behavior_enabled';

	public const option_plural_behavior_enabled = 'plural_behavior_enabled';

	public const option_dropdown_alignment = 'dropdown_alignment';

	protected $dropdown_menu = [];

	/**
	 * Array of attributes
	 *
	 * Set up as follows:
	 *
	 *      array(
	 *      	"value1" => $attributes
	 *      	"value1" => $attributes
	 * 	    )
	 *
	 *	Converted to:
	 *
	 * 	"link_html" will be removed and used for the link_text
	 *
	 * @param unknown $set
	 */
	public function dropdown_menu(array $set = null) {
		if ($set !== null) {
			$this->themeVariables['dropdown_menu'] = $this->dropdown_menu = $set;
			return $this;
		}
		return $this->themeVariables['dropdown_menu'];
	}

	public function dropdown_column($set = null) {
		if ($set !== null) {
			return $this->setOption('dropdown_column', $set);
		}
		return $this->option('dropdown_column', $this->column() . '_dropdown');
	}

	public function dropdown_name($set = null) {
		if ($set !== null) {
			return $this->setOption('dropdown_name', $set);
		}
		return $this->option('dropdown_name', $this->name() . '_dropdown');
	}

	public function dropdown_id($set = null) {
		if ($set !== null) {
			return $this->setOption('dropdown_id', $set);
		}
		return $this->option('dropdown_id', $this->id() . '_dropdown');
	}

	public function dropdown_default($set = null) {
		if ($set === null) {
			return $this->themeVariables['dropdown_default'] ?? null;
		}
		$this->themeVariables['dropdown_default'] = $set;
		return $this;
	}

	public function dropdown_alignment($set = null) {
		if ($set !== null) {
			if (!in_array($set, [
				'left',
				'right',
			])) {
				throw new Exception_Semantics('Requires value of left or right (passed {value})', [
					'value' => $set,
				]);
			}
			return $this->setOption(self::option_dropdown_alignment, $set);
		}
		return $this->option(self::option_dropdown_alignment, 'right');
	}

	public function dropdown_value($set = null) {
		$name = $this->dropdown_name();
		if ($set === null) {
			return $this->object->get($name);
		}
		$this->object->set($name, $set);
		return $this;
	}

	public function buttonLabel($set = null) {
		if ($set !== null) {
			$this->themeVariables[self::option_button_label] = $set;
			$this->setOption(self::option_button_label, $set);
			return $this;
		}
		return $this->option(self::option_button_label);
	}

	/**
	 * Make the menu act like a selection list
	 *
	 * @param string $set
	 * @return Control_Text_Dropdown|boolean
	 */
	public function select_behavior_enabled($set = null) {
		return $set === null ? $this->optionBool(self::option_select_behavior_enabled) : $this->setOption(self::option_select_behavior_enabled, toBool($set));
	}

	/**
	 * Make the menu support plural words reflecting the state of the main input. Only works with select_behavior_enabled set to true.
	 *
	 * Uses data-noun and data-content attributes of each menu item, as follows:
	 *
	 * <a data-noun="hour" data-content="{noun} of service">hour of service</a>
	 *
	 * `data-noun` is required, and contains the singular form of the noun to be pluralized depending on the input value
	 * `data-content` is a simple template for the content of tag which is to be updated with the plural form when needed. You can embed HTML, etc. in here as needed. If not specified, the default value is "{noun}". The term {noun} will be replaced
	 *
	 * @param string $set
	 * @return Control_Text_Dropdown|boolean
	 */
	public function plural_behavior_enabled($set = null) {
		return $set === null ? $this->optionBool(self::option_plural_behavior_enabled) : $this->setOption(self::option_plural_behavior_enabled, toBool($set));
	}

	public function load() {
		$this->object->set($this->dropdown_column(), $this->request->get($this->dropdown_name()));
		return parent::load();
	}

	public function validate(): bool {
		$menu_value = $this->object->get($this->dropdown_column());
		$menu_dropdown = $this->dropdown_menu();
		$result = true;
		if (!empty($menu_value)) {
			if (!array_key_exists($menu_value, $menu_dropdown)) {
				$this->error($this->option('menu_error', $this->application->locale->__("Invalid selection in {label} <!-- $menu_value -->", $this->options())));
				$result = !$this->required();
			}
		} else {
			if ($this->required()) {
				$this->error($this->requiredError(), $this->dropdown_column());
				$result = false;
			}
		}
		return parent::validate() && $result;
	}

	public function themeVariables(): array {
		return [
			'dropdown_id' => $this->dropdown_id(),
			'dropdown_name' => $this->dropdown_name(),
			'dropdown_column' => $this->dropdown_column(),
			'dropdown_value' => $this->dropdown_value(),
		] + parent::themeVariables();
	}
}
