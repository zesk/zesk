<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/control/filter.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 *            Created on Tue Jul 15 16:28:30 EDT 2008
 */
class Control_Filter extends Control {
	protected $theme = "control/filter";
	
	/**
	 * Header theme
	 *
	 * @var string
	 */
	protected $theme_prefix = "control/filter/prefix";
	/**
	 * Header theme
	 *
	 * @var string
	 */
	protected $theme_header = "control/filter/header";
	
	/**
	 * Row tag
	 */
	protected $filter_tag = "form";
	/**
	 * Row attributes
	 * 
	 * @var array
	 */
	protected $filter_attributes = array(
		"class" => "navbar-form",
		"role" => "filter",
		"method" => "GET"
	);
	
	/**
	 * Footer theme
	 *
	 * @var string
	 */
	protected $theme_footer = "control/filter/footer";
	/**
	 * Suffix theme
	 *
	 * @var string
	 */
	protected $theme_suffix = "control/filter/suffix";
	
	/**
	 *
	 * @var array
	 */
	protected $widgets = array();
	
	/**
	 * Cell tag
	 * 
	 * @var array
	 */
	protected $widget_tag = "div";
	
	/**
	 * Cell attributes
	 * 
	 * @var array
	 */
	protected $widget_attributes = array(
		"class" => "form-group"
	);
	protected $render_children = false;
	protected $traverse = true;
	
	/**
	 * Format theme as replacement strings
	 *
	 * @var string
	 */
	protected $theme_widgets = null;
	function model() {
		return new Model();
	}
	protected function init_defaults() {
		foreach ($this->children as $child) {
			$name = $child->name();
			$value = $this->request->get($name);
			$ignore = $child->load_ignore_values();
			if (!is_array($ignore)) {
				log::warning('Child ignore values is not array: {class} {opts}', array(
					'class' => get_class($child),
					'opts' => $child->option()
				));
			} else if (!in_array($value, $ignore, true)) {
				$child->default_value($value);
			}
		}
	}
	protected function initialize() {
		$this->names('filter', 'filter');
		$this->set_option('query_column', false);
		$this->children($this->hook('filters'));
		parent::initialize();
		$this->init_defaults();
	}
	
	public function theme_variables() {
		return array(
			'theme_prefix' => $this->theme_prefix,
			'theme_header' => $this->theme_header,
			'filter_tag' => $this->filter_tag,
			'filter_attributes' => $this->filter_attributes,
			'widget_tag' => $this->widget_tag,
			'widget_attributes' => $this->widget_attributes,
			'widgets' => $this->children(),
			'theme_widgets' => $this->theme_widgets,
			'theme_footer' => $this->theme_footer,
			'theme_suffix' => $this->theme_suffix
		) + parent::theme_variables() + $this->options;
	}
}

