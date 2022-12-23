<?php
/**
 * @package zesk
 * @subpackage world
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.*
 */
declare(strict_types=1);

namespace zesk\World;

class Control_Currency extends Control_Select_ORM {
	protected $class = 'zesk\\Currency';

	protected $options = [
		'escape_values' => false,
	];

	protected function hook_options() {
		$options = [];
		/* @var $object Currency */
		foreach ($this->application->ormRegistry($this->class)
			->querySelect()
			->ormWhat()
			->order_by('name')
			->ormIterator() as $object) {
			$options[$object->id()] = $object->applyMap([
				'label' => '{name} ({symbol})',
				'data-symbol' => '{symbol}',
				'data-format' => '{format}',
				'data-precision' => '{precision}',
			]);
		}
		return $options;
	}
}
