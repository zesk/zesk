<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

$value = $this->value;
/* @var $value Content_Image */
if ($value instanceof Content_Image) {
	echo $this->theme('content/image', [
		'object' => $value,
		'width' => $this->width,
		'height' => $this->height,
	]);
}
$widget = $this->widget;
/* @var $widget Control_Content_Image */

$attributes += $widget->input_attributes() + $widget->data_attributes();
$attributes['type'] = 'file';
echo HTML::tag('input', $attributes);
