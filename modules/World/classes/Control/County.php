<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

class Control_County extends Control_Select_ORM {
	protected string $class = 'zesk\\County';

	protected array $options = [
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
