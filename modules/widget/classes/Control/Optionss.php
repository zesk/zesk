<?php
/**
 * Widget which allows selection of multiple options
 *
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
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
	protected $force_value = false;
	
	/**
	 * Needs to default to null so will be initialized in _init_control_options - unless an alternate
	 * method is used to
	 * track whether this has been initialized.
	 *
	 * @var array
	 */
	protected $control_options = null;
	
	/**
	 * If null, then no novalue.
	 * If string, then there's a noname/novalue pair.
	 *
	 * @var string
	 */
	protected $novalue = null;
	
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
	protected function initialize() {
		$this->_init_no_pair();
		$this->_init_control_options();
		$this->_init_default_option();
		parent::initialize();
	}
	private function _init_no_pair() {
		$this->novalue = avalue($this->options, 'noname') ? strval(avalue($this->options, 'novalue', '')) : null;
	}
	private function _init_control_options() {
		if (is_array($this->control_options)) {
			return;
		}
		$this->control_options = $this->option_array('options', null);
		if (is_array($this->control_options)) {
			return;
		}
		$this->control_options = $this->call_hook_arguments('options', array(), $this->control_options);
		if (!is_array($this->control_options)) {
			$this->control_options = array();
		}
	}
	private function _init_default_option() {
		$default = avalue($this->options, 'default');
		if ($this->has_control_option($default)) {
			return;
		}
		$noname = avalue($this->options, 'noname');
		if ($noname) {
			$this->options['default'] = strval(avalue($this->options, 'novalue', ''));
			return;
		}
		$this->options['default'] = key($this->control_options);
	}
	private function ellipsis_options(array $options) {
		$show_size = $this->show_size();
		if (empty($show_size) || $show_size < 0) {
			return $options;
		}
		$ellipsis = $this->option("ellipsis", "...");
		$new_options = array();
		foreach ($options as $k => $v) {
			if (is_array($v)) {
				$k = StringTools::ellipsis_word($k, $show_size, $ellipsis);
				$new_options[$k] = self::ellipsis_options($v);
			} else {
				$new_options[$k] = StringTools::ellipsis_word($v, $show_size, $ellipsis);
			}
		}
		return $new_options;
	}
	/**
	 *
	 * @param unknown $key
	 * @return boolean
	 */
	private function _has_option_group($key) {
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
		$noname = avalue($this->options, 'noname');
		if ($noname && $key === strval(avalue($this->options, 'novalue', ''))) {
			return true;
		}
		if (to_bool(avalue($this->options, 'optgroup'))) {
			return $this->_has_option_group($key);
		}
		if (!is_array($this->control_options)) {
			backtrace();
		}
		if (array_key_exists($key, $this->control_options)) {
			return true;
		}
		return $this->_has_option_group($key);
	}
	function force_value($set = null) {
		if (is_bool($set)) {
			$this->force_value = $set;
		}
		return $this->force_value;
	}
	public function value_selector() {
		$id = $this->id();
		return "#$id option:selected";
	}
	public function theme_variables() {
		if (!is_array($this->control_options)) {
			dump($this->control_options);
			backtrace();
		}
		return array(
			'original_options' => $this->control_options,
			'control_options' => $this->ellipsis_options($this->control_options)
		) + parent::theme_variables();
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
			$this->call_hook("control_options_changed");
			return $this;
		}
		return $this->control_options;
	}
	public function noname($set = null) {
		if ($set !== null) {
			return $this->set_option('noname', $set);
		}
		return $this->option('noname');
	}
	public function novalue($set = null) {
		if ($set !== null) {
			return $this->set_option('novalue', $set);
		}
		return $this->option('novalue');
	}
}

