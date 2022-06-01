<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 *            Created on Tue Jul 15 16:28:30 EDT 2008
 */

namespace zesk;

class Control_Filter extends Control {
	/**
	 * Header theme
	 *
	 * @var string
	 */
	protected $theme_prefix = 'zesk/control/filter/prefix';

	/**
	 * Header theme
	 *
	 * @var string
	 */
	protected $theme_header = 'zesk/control/filter/header';

	/**
	 * Row tag
	 */
	protected $filter_tag = 'form';

	/**
	 * Row attributes
	 *
	 * @var array
	 */
	protected $filter_attributes = [
		'class' => 'navbar-form',
		'role' => 'filter',
		'method' => 'GET',
	];

	/**
	 * Footer theme
	 *
	 * @var string|iterable
	 */
	protected string|iterable $theme_footer = 'zesk/control/filter/footer';

	/**
	 * Suffix theme
	 *
	 * @var string|iterable
	 */
	protected string|iterable $theme_suffix = 'zesk/control/filter/suffix';

	/**
	 *
	 * @var array
	 */
	protected array $widgets = [];

	/**
	 * Cell tag
	 *
	 * @var array
	 */
	protected string $widget_tag = 'div';

	/**
	 * Cell attributes
	 *
	 * @var array
	 */
	protected array $widget_attributes = [
		'class' => 'form-group',
	];

	/**
	 *
	 * @var string
	 */
	protected bool $render_children = false;

	/**
	 *
	 * @var string
	 */
	protected bool $traverse = true;

	/**
	 * Format theme as replacement strings
	 *
	 * @var string
	 */
	protected $theme_widgets = null;

	/**
	 *
	 * @see Widget::model()
	 * @return ORM
	 */
	public function model(): ORM {
		return new ORM($this->application);
	}

	/**
	 *
	 */
	protected function init_defaults(): void {
		foreach ($this->children as $child) {
			$name = $child->name();
			$value = $this->request->get($name);
			$ignore = $child->load_ignore_values();
			if (!is_array($ignore)) {
				$this->application->logger->warning('Child ignore values is not array: {class} {opts}', [
					'class' => get_class($child),
					'opts' => $child->options(),
				]);
			} elseif (!in_array($value, $ignore, true)) {
				$child->default_value($value);
			}
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Widget::initialize()
	 */
	protected function initialize(): void {
		if (!$this->name()) {
			$this->names('filter', 'filter');
		}
		$this->setOption('query_column', false);
		$this->children($this->call_hook('filters'));
		parent::initialize();
		$this->init_defaults();
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Widget::themeVariables()
	 */
	public function themeVariables(): array {
		return [
				'theme_prefix' => $this->theme_prefix,
				'theme_header' => $this->theme_header,
				'filter_tag' => $this->filter_tag,
				'filter_attributes' => $this->filter_attributes,
				'widget_tag' => $this->widget_tag,
				'widget_attributes' => $this->widget_attributes,
				'widgets' => $this->children(),
				'theme_widgets' => $this->theme_widgets,
				'theme_footer' => $this->theme_footer,
				'theme_suffix' => $this->theme_suffix,
			] + parent::themeVariables() + $this->options;
	}
}
