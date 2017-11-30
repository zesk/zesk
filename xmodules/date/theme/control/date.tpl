<?php
namespace zesk;

/* @var $widget Control_Date */
$widget = $this->widget;
/**
 * Module_Date: Control_Date::render
 */
$col = $this->column;
$name = $this->name;
$empty_string = $this->empty_string;
$object = $this->object;

if (empty($empty_string)) {
	$empty_string = __("View_Date:=Not set.");
}

$object_value = $object->get($col);
if ($object_value === null) {
	$value = "";
} else if (is_date($object_value)) {
	$ts = parse_time($object[$col]);
	$value = date("Y-m-d", $ts);
} else if ($object_value instanceof Timestamp) {
	$value = $object_value->format('{YYYY}-{MM}-{DD}');
} else {
	$value = "";
}

$formname = $this->widget->form_name();

$input_id = "Show_$name";
$attr = array();
$attr['type'] = "text";
$attr['name'] = $input_id;
$attr['id'] = $input_id;
$attr['class'] = "input-text form-control";

$response = $this->response;
/* @var $response zesk\Response_Text_HTML */
$response->javascript('/share/zesk/js/zesk.js', array(
	'weight' => 'first'
));
$response->javascript('/share/zesk/js/zesk-date.js', array(
	"share" => true
));
$response->javascript('/share/date/js/date.js');

$js_options = array();

//$response->jquery("\$(\"#$input_id\").control_date(" . json_encode($js_options) . ")");

$response->css('/share/date/css/date.css', array(
	"media" => "screen"
));

$settings = array(
	'class' => 'control-date',
	'data-widget' => 'control-date'
);
$empty_string = $widget->empty_string();
if ($empty_string) {
	$settings['data-empty-string'] = $empty_string;
}

echo HTML::tag_open('div', $settings + $widget->data_attributes());

echo HTML::tag("input", $attr, null);
echo HTML::hidden($name, $value);
echo HTML::tag_close('div');

