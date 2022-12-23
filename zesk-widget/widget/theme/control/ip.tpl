<?php declare(strict_types=1);
namespace zesk;

/* @var $widget Control_IP */
$widget = $this->widget;
$name = $this->name;

$value = $this->value;
if (is_numeric($value)) {
	$value = long2ip($value);
} else {
	$value = $widget->empty_string();
}
$attrs = [];
$attrs['placeholder'] = __('IP Address');
$attrs['name'] = $name;
$attrs['id'] ??= $attrs['name'];
$attrs['value'] = $value;
$attrs = $widget->inputAttributes() + $attrs;

echo HTML::tag('input', $attrs, null);
