<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

echo $this->theme('zesk/control/text', array(
	'name' => $this->name . '_search',
	'data-target' => 'control-objects-' . $this->name,
	'data-controller' => $this->controller_url
));

$html = "";
if (can_iterate($this->objects)) {
	foreach ($this->objects as $object) {
		$html .= $this->theme($this->theme_object, array(
			"object" => $object
		));
	}
}

echo HTML::tag('div', array(
	"class" => CSS::add_class("control-objects-results", $this->class_results),
	"id" => "control-objects-" . $this->name
), $html);
