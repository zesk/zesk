<?php
namespace zesk;

class Test_Widget extends Test_Unit {

	function test_default_options() {
		$class = null;
		Widget::default_options($class);
		echo basename(__FILE__) . ": success\n";
	}

	function test_factory() {
		$class = "Control";
		$options = null;
		Widget::factory($class, $options);
		echo basename(__FILE__) . ": success\n";
	}

	function test_inherit_options() {
		$options = null;
		$class = null;
		Widget::inherit_options($options, $class);
		echo basename(__FILE__) . ": success\n";
	}

	function test_input_attribute_names() {
		$types = false;
		Widget::input_attribute_names($types);
		echo basename(__FILE__) . ": success\n";
	}

	function test_basics() {
		$options = false;
		$testx = new Widget($options);

		$column = "col";
		$label = "label";
		$name = "name";
		$testx->names($column, $label, $name);

		$object = new Model();
		$testx->execute($object);

		$name = 'form-name';
		$testx->form_name($name);

		$default = 'form';
		$testx->form_name($default);

		$parent = new Widget();
		$testx->parent($parent);

		$required = true;
		$testx->required($required);

		$testx->required();

		$default = 'no_name';
		$testx->column($default);

		$testx->name();

		$testx->label();

		$testx->is_visible($object);

		$testx->clear();

		$testx->errors();

		$testx->messages();

		$testx->has_errors();

		$sType = 'err';
		$sMessage = null;
		$testx->error($sType, $sMessage);

		$sType = 'mess';
		$sMessage = null;
		$testx->message($sType, $sMessage);

		$testx->isContinue();

		$mixed = null;
		$testx->error($mixed);

		$testx->error_required();

		$types = false;
		$testx->input_attribute_names($types);

		$types = false;
		$testx->input_attributes($types);

		$default = "Hey, dude.";
		$this->assert_equal($testx->empty_string($default)->empty_string(), $default);

		$data = null;
		$testx->suffix($data);

		$data = null;
		$testx->prefix($data);

		$testx->language();

		$testx->locale();

		$testx->show_size();
	}
}