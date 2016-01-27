<?php

echo $this->theme('control/text', array(
	'name' => $this->name . '_search',
	'data-target' => 'control-objects-' . $this->name,
	'data-controller' => $this->controller_url,
));

$html = "";
if ($this->objects instanceof Iterator) {
	foreach ($this->objects as $object) {
		$html .= $this->theme($this->theme_object, array(
			"object" => $object
		));
	}
}

echo html::tag('div', array(
	"class" => css::add_class("control-objects-results", $this->class_results),
	"id" => "control-objects-" . $this->name
), $html);
