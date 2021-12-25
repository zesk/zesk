<?php declare(strict_types=1);
namespace zesk;

class Control_Edit_Horizontal extends Control_Edit {
	protected $form_attributes = [
		"class" => "edit form-horizontal",
		"method" => "post",
		"role" => "form",
	];

	/**
	 *
	 * @var array
	 */
	protected $label_attributes = [];

	protected $widget_attributes = [
		'class' => 'form-group',
	];

	protected $widget_wrap_tag = "div";

	/**
	 * Optional wrap attributes for each widget
	 *
	 * @var array
	 */
	protected $widget_wrap_attributes = [];

	/**
	 * Optional wrap attributes for each widget which have no label
	 *
	 * @var array
	 */
	protected $nolabel_widget_wrap_attributes = [];

	protected function initialize(): void {
		parent::initialize();
		$ncols = $this->option('layout_column_count', 12);
		$quarter = round($ncols / 4);
		$three_quarters = $ncols - $quarter;
		$this->label_attributes = HTML::add_class($this->label_attributes, "col-sm-$quarter");
		$this->widget_wrap_attributes = HTML::add_class($this->widget_wrap_attributes, "col-sm-$three_quarters");
		$this->nolabel_widget_wrap_attributes = HTML::add_class($this->nolabel_widget_wrap_attributes, "col-sm-$ncols");
	}
}
