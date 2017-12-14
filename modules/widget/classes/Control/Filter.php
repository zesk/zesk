<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Control/Filter.php $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 *            Created on Tue Jul 15 16:28:30 EDT 2008
 */
namespace zesk;

class Control_Filter extends Control {
	/**
	 * Header theme
	 *
	 * @var string
	 */
	protected $theme_prefix = "zesk/control/filter/prefix";
	/**
	 * Header theme
	 *
	 * @var string
	 */
	protected $theme_header = "zesk/control/filter/header";
	
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
	protected $theme_footer = "zesk/control/filter/footer";
	/**
	 * Suffix theme
	 *
	 * @var string
	 */
	protected $theme_suffix = "zesk/control/filter/suffix";
	
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
	/**
	 * 
	 * @var string
	 */
	protected $render_children = false;
	
	/**
	 * 
	 * @var string
	 */
	protected $traverse = true;
	
	/**
	 * Format theme as replacement strings
	 *
	 * @var string
	 */
	protected $theme_widgets = null;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Widget::model()
	 */
	function model() {
		return new Model($this->application);
	}
	
	/**
	 * 
	 */
	protected function init_defaults() {
		foreach ($this->children as $child) {
			$name = $child->name();
			$value = $this->request->get($name);
			$ignore = $child->load_ignore_values();
			if (!is_array($ignore)) {
				$this->application->logger->warning('Child ignore values is not array: {class} {opts}', array(
					'class' => get_class($child),
					'opts' => $child->option()
				));
			} else if (!in_array($value, $ignore, true)) {
				$child->default_value($value);
			}
		}
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Widget::initialize()
	 */
	protected function initialize() {
		$this->names('filter', 'filter');
		$this->set_option('query_column', false);
		$this->children($this->call_hook('filters'));
		parent::initialize();
		$this->init_defaults();
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Widget::theme_variables()
	 */
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

