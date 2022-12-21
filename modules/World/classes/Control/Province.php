<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

class Control_Province extends Control_Select_ORM {
	protected string $class = Province::class;

	protected array $options = [
		'text_column' => 'name', 'id_column' => 'id',
	];

	protected function initialize(): void {
		$this->options['noname'] = $this->application->locale->__(__CLASS__ . ':=All states');
		parent::initialize();
	}
}
