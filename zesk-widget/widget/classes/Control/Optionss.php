<?php declare(strict_types=1);
/**
 * Widget which allows selection of multiple options
 *
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 *
 * @see Control_Select
 * @see Control_Select_Other
 * @see Control_Select_ORM
 */
namespace zesk;

/**
 * @todo rename this to Control_Options when we're fully PHP7 to avoid
 * the deprecated ->control_options constructor confusion in PHP5.
 *
 * @author kent
 *
 */
class Control_Optionss extends Control {
	/**
	 *
	 * @var boolean
	 */
	protected bool $force_value = false;

	/**
	 * Needs to default to null so will be initialized in _init_control_options - unless an alternate
	 * method is used to
	 * track whether this has been initialized.
	 *
	 * @var array
	 */
	protected ?array $control_options = null;

	/**
	 * If null, then no novalue.
	 * If string, then there's a noname/novalue pair.
	 *
	 * @var string
	 */
	protected ?string $novalue = null;

	/**
	 * Allow use of __construct and ->control_options function which matches this class name - old
	 * style constructor
	 *
	 * @param mixed $a0
	 * @param mixed $a1
	 * @param mixed $a2
	 */
	public function __construct($a0 = null, $a1 = null, $a2 = null) {
		parent::__construct($a0, $a1, $a2);
	}

	/**
	 * Initialize the widget
	 */
	protected function initialize(): void {
		$this->_init_no_pair();
		$this->_init_control_options();
		$this->_init_default_option();
		parent::initialize();
	}

	private function _init_no_pair(): void {
		$this->novalue = isset($this->options['noname']) ? strval($this->options['novalue'] ?? '') : null;
	}

	private function _init_control_options(): void {
		if (is_array($this->control_options)) {
			return;
		}
		$this->control_options = $this->optionArray('options');
		$new_options = $this->callHookArguments('options', [], $this->control_options);
		if (is_array($new_options)) {
			$this->control_options = $new_options;
		}
	}

	private function _init_default_option(): void {
		$default = $this->options['default'] ?? null;
		if ($this->has_control_option($default)) {
			return;
		}
		$noname = $this->options['noname'] ?? null;
		if ($noname) {
			$this->options['default'] = strval($this->options['novalue'] ?? '');
			return;
		}
		$this->options['default'] = key($this->control_options);
	}

	private function ellipsis_options(array $options) {
		$show_size = $this->showSize();
		if (empty($show_size) || $show_size < 0) {
			return $options;
		}
		$ellipsis = $this->option('ellipsis', '...');
		$new_options = [];
		foreach ($options as $k => $v) {
			if (is_array($v)) {
				$k = StringTools::ellipsisWord($k, $show_size, $ellipsis);
				$new_options[$k] = $this->ellipsis_options($v);
			} else {
				$new_options[$k] = StringTools::ellipsisWord($v, $show_size, $ellipsis);
			}
		}
		return $new_options;
	}

	/**
	 *
	 * @param unknown $key
	 * @return boolean
	 */
	private function _hasOption_group($key) {
		$options = $this->control_options;
		foreach ($options as $k => $optgroup) {
			if (is_array($optgroup) && array_key_exists($key, $optgroup)) {
				return true;
			}
		}
		return false;
	}

	/**
	 *
	 * @param unknown $key
	 * @return boolean
	 */
	protected function has_control_option($key) {
		$key = strval($key);
		$noname = $this->options['noname'] ?? null;
		if ($noname && $key === strval($this->options['novalue'] ?? '')) {
			return true;
		}
		if (toBool($this->options['optgroup'] ?? null)) {
			return $this->_hasOption_group($key);
		}
		if (array_key_exists($key, $this->control_options)) {
			return true;
		}
		return $this->_hasOption_group($key);
	}

	public function force_value($set = null) {
		if (is_bool($set)) {
			$this->force_value = $set;
		}
		return $this->force_value;
	}

	public function value_selector() {
		$id = $this->id();
		return "#$id option:selected";
	}

	public function themeVariables(): array {
		assert(is_array($this->control_options));
		return [
			'original_options' => $this->control_options,
			'control_options' => $this->ellipsis_options($this->control_options),
		] + parent::themeVariables();
	}

	/**
	 * @todo This can cause constructor errors
	 *
	 * @param array $set
	 * @return \zesk\Control_Options
	 */
	public function control_options(array $set = null) {
		if (is_array($set)) {
			$this->control_options = $set;
			$this->callHook('control_options_changed');
			return $this;
		}
		return $this->control_options;
	}

	public function noname($set = null) {
		if ($set !== null) {
			return $this->setOption('noname', $set);
		}
		return $this->option('noname');
	}

	public function novalue($set = null) {
		if ($set !== null) {
			return $this->setOption('novalue', $set);
		}
		return $this->option('novalue');
	}
}
