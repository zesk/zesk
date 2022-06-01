<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage inplace
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
abstract class Control_List_InPlace extends Control_List {
	/**
	 *
	 * @var string
	 */
	protected $theme_empty = 'zesk/control/list/inplace/empty';

	/**
	 *
	 * @var string
	 */
	protected $theme_widgets = 'zesk/control/list/inplace/widgets';

	/**
	 *
	 * @var string
	 */
	protected $theme_prefix = 'zesk/control/list/inplace/prefix';

	/**
	 *
	 * @var string
	 */
	protected $theme_suffix = 'zesk/control/list/inplace/suffix';

	/**
	 *
	 * @var string
	 */
	protected $theme_header = 'zesk/control/list/inplace/header';

	/**
	 *
	 * @var string
	 */
	protected $theme_footer = 'zesk/control/list/inplace/footer';

	/**
	 *
	 * @var array
	 */
	protected $widget_attributes = [];

	/**
	 *
	 * @var string
	 */
	protected $list_tag = 'div';

	protected $list_attributes = [
		'class' => 'list list-inplace',
	];

	protected $total_title = null;

	protected $hierarchy_column = null;

	protected $row_attributes = [
		'class' => 'row',
		'id' => 'row-{id}',
		'data-id' => '{id}',
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Control_List::initialize()
	 */
	protected function initialize(): void {
		parent::initialize();
		$id = $this->id();
		if (!$id) {
			$this->id($id = 'control-list-inplace-' . $this->response->id_counter());
		}
		$this->list_attributes['id'] = $id;
		$this->addWrap('div', '.control-list-inplace');
	}

	/**
	 *
	 * @return \zesk\Widget[]
	 */
	protected function hook_widgets() {
		$locale = $this->application->locale;
		$widgets[] = $w = $this->widget_factory('zesk\Control_Hidden')
			->names('id', $locale->__('ID'), 'id[]')
			->id(false)
			->addClass('selection-id');
		$widgets[] = $w = $this->widget_factory('zesk\View_Text')->names('name');

		return $widgets;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Control_List::themeVariables()
	 */
	public function themeVariables(): array {
		$locale = $this->application->locale;
		return [
			'hierarchy_column' => $this->hierarchy_column,
			'total_title' => $locale->__($this->total_title),
			'target' => $this->option('target'),
			'dismiss_button' => $this->optionBool('dismiss_button', true),
			'current_user' => $this->user(),
		] + parent::themeVariables();
	}

	/**
	 * Setter/getter for hierarchy column
	 *
	 * @param string $set Set value
	 * @return string|\zesk\Control_List_InPlace
	 */
	public function hierarchy_column($set = null) {
		if ($set === null) {
			return $this->hierarchy_column;
		}
		$this->hierarchy_column = $set;
		return $this;
	}
}
