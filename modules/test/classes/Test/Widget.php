<?php
namespace zesk;

class Test_Widget extends Test_Unit {
	function test_default_options() {
		$class = null;
		Widget::default_options($this->application, $class);
	}
	function test_inherit_options() {
		$options = null;
		$class = null;
		Widget::inherit_options($this->application, $options, $class);
	}
	function widget_tests(Widget $testx) {
		$column = "col";
		$label = "label";
		$name = "name";
		$testx->names($column, $label, $name);
		
		$object = new Model($this->application);
		$testx->execute($object);
		
		$name = 'form-name';
		$testx->form_name($name);
		
		$default = 'form';
		$testx->form_name($default);
		
		$parent = $testx->widget_factory("zesk\\Widget");
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
		
		$mixed = null;
		$testx->error($mixed);
		
		$testx->error_required();
		
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
	
	/**
	 * 
	 */
	function test_basics() {
		$testx = $this->application->widget_factory("zesk\\Widget");
		
		$this->widget_tests($testx);
	}
}
