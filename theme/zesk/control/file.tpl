<?php
$object = $this->object;

/* @var $widget Control_File */

$widget = $this->widget;
$col = $this->column;
$name = $this->name;
$value = $this->value;

$filecolumn = $widget->option("filecolumn", $col . "_FileName");

$actual_name = $value;
echo html::div(array(
	"id" => $name . "_widget",
	"class" => css::add_class("control-file-filename", empty($actual_name) ? "empty" : "")
), empty($actual_name) ? "" : html::tag("span", "class=\"filename\"", $actual_name));

$attrs = $widget->options_include($widget->input_attribute_names());
$attrs["name"] = $name . "_file";
$attrs["id"] = $widget->option('id', $name);
$attrs["type"] = "file";
if ($this->class) {
	$attrs["class"] = $this->class;
}
echo html::tag("input", $widget->attributes($attrs, "input"), null);

$attrs = array();
$attrs["type"] = "hidden";
$attrs["id"] = $filecolumn;
$attrs["name"] = $filecolumn;
$attrs["value"] = strval($object->get($filecolumn, ""));

echo html::tag("input", $attrs, null);

$attrs["name"] = $name;
$attrs["value"] = strval($value);

echo html::tag("input", $attrs, null);
if (!empty($value)) {
	echo $this->theme('control/file/delete');
}
