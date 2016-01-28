<?php
$value = $this->value;
/* @var $value Content_Image */
if ($value instanceof Content_Image) {
	echo theme('content/image', array(
		"object" => $value,
		"width" => $this->width,
		"height" => $this->height,
	));
}
$widget = $this->widget;
/* @var $widget Control_Content_Image */

$attributes += $widget->input_attributes() + $widget->data_attributes();
$attributes['type'] = 'file';
echo html::tag('input', $attributes);