<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/* @var $this Template */
$object = $this->object;
/* @var $object Model */
$widget = $this->widget;
/* @var $widget Control_Text */
$name = $this->name;
/* @var $name string */
$value = $this->value;
$variables = $this->variables;

$ia = ArrayTools::filter(to_array($variables), 'style;class;onclick;onchange;ondblclick;onmousedown;onmouseup;onmousemove;onmouseout;onmouseover;onkeypress;onkeydown;onkeyup;onfocus;onblur');

$ia += $widget->dataAttributes();

$ia['id'] = $this->id;
$ia['name'] = $name;

$class = strval($this->class);
if ($this->required) {
	$class = CSS::addClass($class, 'required');
}
$ia['class'] = CSS::addClass($class, 'form-control');

$ia['placeholder'] = $this->placeholder;

if (empty($value)) {
	$value = $this->default;
}
if ($this->textarea) {
	$ia['rows'] = $this->rows;
	$ia['cols'] = $this->cols;

	echo HTML::tag_open('textarea', $ia) . htmlspecialchars(strval($value)) . HTML::tag_close('textarea');
} else {
	$ia['type'] = $this->password ? 'password' : 'text';
	$ia = $object->applyMap($ia) + [
			'value' => $value,
		];
	$input = HTML::tag('input', $ia);
	if ($this->input_group_addon) {
		$html = HTML::span($this->get('input_group_class', '.input-group-addon'), $this->input_group_addon);
		echo HTML::tag('div', '.input-group', $this->input_group_addon_left ? $html . $input : $input . $html);
	} else {
		echo $input;
	}
}
