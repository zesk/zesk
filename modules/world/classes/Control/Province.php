<?php declare(strict_types=1);
namespace zesk;

class Control_Province extends Control_Select_ORM {
	protected $class = 'zesk\\Province';

	protected $options = [
		'text_column' => 'name',
		'id_column' => 'id',
	];

	protected function initialize(): void {
		$this->options['noname'] = $this->application->locale->__('zesk\\Control_Province:=All states');
		parent::initialize();
	}
}
