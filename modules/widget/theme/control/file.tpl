<?php declare(strict_types=1);
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

$object = $this->object;

/* @var $widget Control_File */

$widget = $this->widget;
$col = $this->column;
$name = $this->name;
$value = $this->value;

$filecolumn = $widget->option("filecolumn", $col . "_FileName");

$actual_name = $value;
echo HTML::div([
	"id" => $name . "_widget",
	"class" => CSS::add_class("control-file-filename", empty($actual_name) ? "empty" : ""),
], empty($actual_name) ? "" : HTML::tag("span", "class=\"filename\"", $actual_name));

$attrs = $widget->options_include(HTML::input_attribute_names());
$attrs["name"] = $name . "_file";
$attrs["id"] = $widget->option('id', $name);
$attrs["type"] = "file";
if ($this->class) {
	$attrs["class"] = $this->class;
}
echo HTML::tag("input", $widget->attributes($attrs, "input"), null);

$attrs = [];
$attrs["type"] = "hidden";
$attrs["id"] = $filecolumn;
$attrs["name"] = $filecolumn;
$attrs["value"] = strval($object->get($filecolumn, ""));

echo HTML::tag("input", $attrs, null);

$attrs["name"] = $name;
$attrs["value"] = strval($value);

echo HTML::tag("input", $attrs, null);
if (!empty($value)) {
	echo $this->theme('control/file/delete');
}
