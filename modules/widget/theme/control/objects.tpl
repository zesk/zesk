<?php declare(strict_types=1);
/**
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk;

echo $this->theme('zesk/control/text', [
	'name' => $this->name . '_search',
	'data-target' => 'control-objects-' . $this->name,
	'data-controller' => $this->controller_url,
]);

$html = '';
if (can_iterate($this->objects)) {
	foreach ($this->objects as $object) {
		$html .= $this->theme($this->theme_object, [
			'object' => $object,
		]);
	}
}

echo HTML::tag('div', [
	'class' => CSS::add_class('control-objects-results', $this->class_results),
	'id' => 'control-objects-' . $this->name,
], $html);
