<?php
use zesk\Object;

class Control_Currency extends Control_Select_Object {
	protected $class = "Currency";

	protected $options = array(
		'escape_values' => false
	);

	protected function hook_options() {
		$options = array();
		/* @var $object Currency */
		foreach (Object::class_query($this->class)->what_object()->order_by("name")->object_iterator() as $object) {
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
