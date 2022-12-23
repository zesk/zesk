<?php declare(strict_types=1);
namespace zesk;

class Control_FieldSet extends Control_Widgets {
	protected $options = [
		'nolabel' => true,
	];

	public function initialize(): void {
		$this->prefix .= HTML::tag('legend', $this->label);
		$this->addWrap('fieldset', $this->setAttributes([
			'class' => 'control-fieldset',
			'id' => $this->id,
		], 'fieldset'));
		parent::initialize();
	}
}
