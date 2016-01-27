<?php

/* @var $this Template */
if (false) {
	$object = $this->object;
	/* @var $object Model */
	$widget = $this->widget;
	/* @var $widget Control_Text */
	$name = $this->name;
	/* @var $name string */
	$value = $this->value;
	$variables = $this->variables;
}
$ia = arr::filter($variables, "style;class;onclick;onchange;ondblclick;onmousedown;onmouseup;onmousemove;onmouseout;onmouseover;onkeypress;onkeydown;onkeyup;onfocus;onblur");

$ia += $widget->data_attributes();

$ia['id'] = $this->id;
$ia["name"] = $name;

$class = $this->class;
if ($this->required) {
	$class = css::add_class($class, "required");
}
$ia['class'] = css::add_class($class, 'form-control');

$ia['placeholder'] = $this->placeholder;

if (empty($value)) {
	$value = $this->default;
}
if ($this->textarea) {
	$ia["rows"] = $this->rows;
	$ia["cols"] = $this->cols;

	echo html::tag_open("textarea", $ia) . htmlspecialchars(strval($value)) . html::tag_close("textarea");
} else {
	$ia["type"] = $this->password ? "password" : "text";
	$ia = $object->apply_map($ia) + array(
		'value' => $value
	);
	$input = html::tag("input", $ia);
	if ($this->input_group_addon) {
		$addon = html::span($this->get('input_group_class', '.input-group-addon'), $this->input_group_addon);
		echo html::tag('div', '.input-group', $this->input_group_addon_left ? $addon . $input : $input . $addon);
	} else {
		echo $input;
	}
}
