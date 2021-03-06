<?php
use zesk\ORM;
use zesk\HTML;

/* @var $object ORM */
$object = $this->object;
/* @var $class_object Class_ORM */
$class_object = $this->class_object;

$format = $this->format;

$value = $this->value;
$method = $this->display_method;
$method_args = $this->display_method_arguments;

if ($this->hidden_input) {
	echo HTML::hidden($this->name, $value);
}

if (!empty($value)) {
	$col_object = null;
	if ($value instanceof ORM) {
		$col_object = $value;
	} elseif (is_numeric($value) && intval($value) !== 0) {
		try {
			$col_object = $this->application->orm_factory($this->object_class, $value)->fetch();
		} catch (Exception $e) {
			$col_object = null;
		}
	}
	if ($col_object) {
		if ($method) {
			echo call_user_func_array(array(
				$col_object,
				$method,
			), $method_args);
			return;
		}
		if ($format) {
			echo $object->apply_map($col_object->apply_map($format));
			return;
		}
		echo $col_object->display_name();
		return;
	}
}
echo $object ? $object->apply_map($this->empty_string) : $this->empty_string;
