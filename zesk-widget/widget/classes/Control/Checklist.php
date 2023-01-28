<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * Created on Tue Jul 15 16:38:07 EDT 2008
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Checklist extends Control_Optionss {
	public const option_checklist_exclusive = 'checklist_exclusive';

	/**
	 *
	 * @var ?array
	 */
	private ?array $widgets_id = null;

	/**
	 *
	 * @var array
	 */
	private array $checkbox_exclusives = [];

	/**
	 * Convert value to/from a string (list)
	 *
	 * @return bool
	 */
	public function valueIsList(): bool {
		return $this->optionBool('value_is_list');
	}

	/**
	 * Convert value to/from a string (list)
	 *
	 * @param bool $set
	 */
	public function setValueIsList(bool $set): self {
		return $this->setOption('value_is_list', $set);
	}

	/**
	 * Getter/setter for columns to display checkboxes in
	 *
	 * @return int
	 */
	public function columns(): int {
		return $this->optionInt('columns');
	}

	/**
	 * @param int $set
	 * @return $this
	 */
	public function setColumns(int $set): self {
		return $this->setOption('columns', $set);
	}

	/**
	 * @param $value
	 * @param $set
	 * @return $this|array
	 * @throws Exception_Parameter
	 */
	public function checkbox_exclusive($value = null, $set = null) {
		if ($value === null) {
			$result = [];
			foreach ($this->children() as $child) {
				if ($child->optionBool(self::option_checklist_exclusive) === $set) {
					$result[] = $child;
				}
			}
			return $result;
		}
		if (is_scalar($value)) {
			$this->checkbox_exclusives[$value] = $set;
			return $this;
		}

		throw new Exception_Parameter('{method} {name} {id} Widget not support for value {type} {value}', [
			'method' => __METHOD__,
			'type' => gettype($value),
			'value' => $value,
		] + $this->options);
	}

	/**
	 * @return void
	 */
	protected function hook_control_options_changed(): void {
		$this->widgets_id = null;
		$this->_init_children(toArray($this->control_options));
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Control_Options::initialize()
	 */
	protected function initialize(): void {
		$options = $this->option('options');
		if (is_array($options)) {
			$this->_init_children($options);
		} else {
			$this->control_options = $this->callHookArguments('options', [], $this->control_options);
			$this->callHook('control_options_changed');
		}
		parent::initialize();
	}

	/**
	 * @param $name
	 * @param $col
	 * @param $label
	 * @param $value
	 * @return Widget
	 * @throws Exception_Semantics
	 */
	private function control_checkbox_factory(string $name, string $col, string $label, string $value) {
		return $this->widgetFactory(Control_Checkbox::class, [
			'name' => $name . '[]',
			'column' => $col,
			'id' => $col,
			'label_checkbox' => $label,
			'checked_value' => $value,
		]);
	}

	/**
	 * @param string $value
	 * @return string
	 */
	private function _child_name(string $value): string {
		return 'checklist-' . $this->name() . "-$value";
	}

	/**
	 * Add children
	 *
	 * @param array $options
	 */
	protected function _init_children(array $options): array {
		if (is_array($this->widgets_id)) {
			return $this->widgets_id;
		}
		$this->widgets_id = [];
		$name = $this->name();
		foreach ($options as $value => $label) {
			$col = $this->_child_name($value);
			$this->widgets_id[$value] = $widget = $this->control_checkbox_factory($name, $col, $label, $value);
			$this->setChild($col, $widget);
			$exclusive = $this->checkbox_exclusives[$value] ?? null;
			if (is_bool($exclusive)) {
				$widget->setOption(self::option_checklist_exclusive, $exclusive);
			}
		}
		return $this->widgets_id;
	}

	/**
	 * Hook intialized
	 */
	protected function hook_initialized(): void {
		$values = $this->callHookArguments('object_value', [], []);
		if (can_iterate($values)) {
			foreach ($values as $value => $label) {
				$value = strval($value);
				if (array_key_exists($value, $this->widgets_id)) {
					$this->widgets_id[$value]->setOption('checked', true);
				}
			}
		}
	}

	/**
	 *
	 * @return string
	 */
	private function option_separator(): string {
		return $this->option('separator', ';');
	}

	/**
	 *
	 * @return array Iterator
	 */
	protected function hook_object_value() {
		if ($this->valueIsList()) {
			$flip_copy = toList($this->value(), toList($this->default_value(), []), $this->option_separator());
			return ArrayTools::valuesFlipCopy($flip_copy);
		}
		return $this->value();
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::load()
	 */
	protected function load(): void {
		$name = $this->name();
		$values = $this->request->getList($name);
		foreach ($values as $value) {
			try {
				$child = $this->findChild($this->_child_name($value));
				$child->setOption('checked', true);
			} catch (Exception_Key) {
				// TODO Handle this
			}
		}
		$column = $this->column();
		$this->object->set($column, $values);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::submit()
	 */
	public function submit(): bool {
		$values = $this->request->getList($this->name());
		if ($this->valueIsList()) {
			$this->value(implode($this->option_separator(), $values));
		} else {
			$this->value($values);
		}
		return true;
	}

	// Debugging only
	// 	private $debug = "";
	// 	public function render(): string {
	// 		return parent::render() . $this->debug;
	// 	}
}
