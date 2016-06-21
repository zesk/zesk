<?php

/* @var $this Template */
$attr = $this->attributes;

$object = $this->object;
$name = $this->name;
$id = $this->id;
$col = $this->column;
$widget = $this->widget;

$attr["type"] = "checkbox";
$attr["value"] = $this->checked_value;
$attr["class"] = $this->class;
$attr["name"] = $name;
$attr["id"] = $id ? $id : null;

$disabled_class = "";
if ($this->getb("disabled")) {
	$disabled = $this->getb("disabled");
	if ((is_bool($disabled) && $disabled) || strtolower($disabled) === "disabled") {
		$attr['disabled'] = 'disabled';
		$disabled_class = " disabled";
	}
}
$cont_name = $name . "_sv";
if ($this->getb("refresh")) {
	$attr["onclick"] = "this.form.$cont_name.value=1;this.form.submit();";
}
if ($this->checked) {
	$attr["checked"] = "checked";
}

$result = $this->input_prefix . html::tag("input", $object ? $object->apply_map($attr) : $attr, null) . $this->input_suffix;
if ($this->getb("refresh")) {
	echo html::hidden($cont_name, '');
}

if ($this->label_checkbox) {
	$label_attr = arr::map_keys(arr::filter($attr, "id"), array(
		"id" => "for"
	));
	$label_attr['class'] = $this->get('checkbox_label_class', null);
	$result = html::tag('div', '.checkbox' . $disabled_class, html::tag("label", $label_attr, $result . $this->label_checkbox));
}
echo $result;
if (!ends($name, ']')) {
	echo html::hidden($name . '_ckbx', 1);
}
