<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage control
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Widgets_Filter extends Control_Widgets {
	/**
	 * Filter control
	 * @var Control_Filter
	 */
	protected $filter = null;

	/**
	 *
	 * @return array
	 */
	protected function hook_filters() {
		return [];
	}

	/**
	 *
	 */
	protected function initialize_filter(): void {
		if ($this->filter === null) {
			$filters = $this->callHook('filters');
			if (count($filters) > 0) {
				$options = $this->options(toList('URI;filter_preserve_include;filter_preserve_exclude;ajax_id;filter_form_id'));
				$options = ArrayTools::keysMap($options, [
					'filter_form_id' => 'form_id',
				]);
				$options['id'] = $options['column'] = 'filter';
				$this->filter = new Control_Filter($this->application, $options);
				$this->filter->children($filters);
				$this->filter->addWrap('div', '.filters');
				$this->addChild($this->filter);

				$this->callHook('initialize_filter');
			}
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Control_Widgets::initialize()
	 */
	protected function initialize(): void {
		$this->initialize_filter();
		parent::initialize();
	}

	/**
	 *
	 * @return \zesk\Control_Filter
	 */
	public function filter() {
		return $this->filter;
	}

	/**
	 * Getter/setter for show_filter option
	 *
	 * @param boolean $set
	 * @return self|boolean
	 */
	public function show_filter($set = null) {
		return $set !== null ? $this->setOption('show_filter', toBool($set)) : $this->optionBool('show_filter', true);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Widget::themeVariables()
	 */
	public function themeVariables(): array {
		return [
			'filter' => $this->filter,
			'show_filter' => $this->show_filter(),
		] + parent::themeVariables();
	}
}
