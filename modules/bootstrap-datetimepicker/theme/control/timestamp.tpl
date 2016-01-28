<?php

/* @var $this Template */
$response = $this->response;
/* @var $response Response_HTML */

$id = $this->id;
if (empty($id)) {
	$this->id = $id = "datetimepicker-" . $response->id_counter();
}
$value = $this->value;
if (empty($value)) {
	$value = $this->default;
}
$format = $this->allow_times ? 'Y-m-d H:i' : 'Y-m-d';
$value = $this->value = date($format, strtotime($value));
$onchange = $this->onchange;

$inline = $this->getb("inline");

echo $this->theme('control/text', array(
	'onchange' => null,
	'class' => $inline ? css::add_class($this->class, 'hidden') : $this->class
));

$options = $this->get(array(
	"inline" => $inline,
	"sideBySide" => true,
	"format" => __("Module_Bootstrap_DateTimePicker::widget_layout_format:=YYYY-MM-DD hh:mm a"),
	"toolbarPlacement" => "bottom",
	"showTodayButton" => true
));
$zformat = "{YYYY}-{MM}-{DD} {hh}:{mm}";
if ($this->data_future_only) {
	$options['minDate'] = $minDate = Timestamp::now()->format($zformat);
} else if ($this->data_past_only) {
	$options['maxDate'] = Timestamp::now()->format($zformat);
}

if ($inline) {
	$options['defaultDate'] = Timestamp::factory(firstarg($this->default, $value))->format($zformat);
}
//	"format_time" => "formatTime",
//	"format_date" => "formatDate",
//	"allow_times" => "allowTimes",
foreach (array(
	"step" => "stepping"
) as $template_key => $js_option) {
	if ($this->has($template_key)) {
		$options[$js_option] = $this->get($template_key);
	}
}
$original_id = $id;
if ($inline) {
	$id = "$id-dtp";
	echo html::div("#$id", "");
}
$jquery = "\$(\"#$id\").datetimepicker(" . json::encode($options) . ")";

if ($onchange) {
	$onchange = js::clean_code($onchange);
	//	$options['*onChangeDateTime'] = "function (dp, \$input) {\n\tvar datetime = new Date(\$input.val());\n\t$onchange;\n}";
}
if ($this->oninit) {
	$oninit = js::clean_code($this->oninit);
	$jquery .= ".on(\"dp.change\", function (e) {\n\t\tvar datetime = e.date.toDate();\n\t$onchange;\n})";
}
if ($inline) {
	$jquery .= ".on(\"dp.change\", function (e) {\n\t\$(\"#$original_id\").val(e.date.format('YYYY-MM-DD HH:mm:ss'));\n})";
}

$jquery .= ";";

$this->response->jquery($jquery);
