<?php
/* @var $widget Control_IP */
$widget = $this->widget;
$name = $this->name;

$value = $this->value;
if (is_numeric($value)) {
	$value = long2ip($value);
} else {
	$value = $widget->empty_string();
}
$attrs = array();
$attrs['placeholder'] = __("IP Address");
$attrs['name'] = $name;
$attrs['id'] = avalue($attrs, 'id', $attrs['name']);
$attrs['value'] = $value;
$attrs = $widget->input_attributes() + $attrs;

echo HTML::tag('input', $attrs, null);

