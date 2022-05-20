<?php declare(strict_types=1);
namespace zesk;

class Control_County extends Control_Select_ORM {
	protected $class = 'zesk\\County';

	protected $options = [
		'text_column' => 'name',
		'id_column' => 'id',
	];

	protected function initialize(): void {
		if (!$this->hasOption('noname')) {
			$this->noname($this->application->locale->__('Control_County:=No county'));
		}
		parent::initialize();
	}
}
