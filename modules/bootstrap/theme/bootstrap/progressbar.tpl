<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this Template */
$minimum = $this->get("minimum", 0);
$maximum = $this->get("maximum", 100);
if (real_equal($maximum - $minimum, 0)) {
	$percent = 0;
} else {
	$percent = round($this->value * 100.0 / ($maximum - $minimum));
}
$label = $this->label;
$text_arguments = $this->geta('text_arguments', array());
$text_arguments += array(
	"percent" => $percent,
	"value" => $this->value
);
echo HTML::tag_open('div', array(
	"class" => CSS::add_class("progress", $this->class)
));

echo __($this->prefix, $text_arguments);

echo HTML::div(array(
	"class" => CSS::add_class("progress-bar", $this->progressbar_class),
	"role" => "progressbar",
	"aria-valuenow" => $this->value,
	"aria-valuemin" => $this->get("minimum", 0),
	"aria-valuemax" => $maximum,
	"style" => "width: $percent%"
), ($label ? __($label, $text_arguments) : HTML::etag("span", ".sr-only", __($this->accessible_label, $text_arguments))));

echo map($this->suffix, $text_arguments);

echo HTML::tag_close('div');
