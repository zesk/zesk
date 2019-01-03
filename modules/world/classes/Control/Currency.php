<?php
namespace zesk;

class Control_Currency extends Control_Select_ORM {
	protected $class = "zesk\\Currency";

	protected $options = array(
		'escape_values' => false,
	);

	protected function hook_options() {
		$options = array();
		/* @var $object Currency */
		foreach ($this->application->orm_registry($this->class)
			->query_select()
			->what_object()
			->order_by("name")
			->orm_iterator() as $object) {
			$options[$object->id()] = $object->apply_map(array(
				'label' => '{name} ({symbol})',
				'data-symbol' => '{symbol}',
				'data-format' => '{format}',
				'data-precision' => '{precision}',
			));
		}
		return $options;
	}
}
