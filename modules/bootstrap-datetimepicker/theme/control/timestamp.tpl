<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk \zesk\Kernel */
	
	$application = $this->application;
	/* @var $application \zesk\Application */
	
	$session = $this->session;
	/* @var $session \zesk\Session */
	
	$router = $this->router;
	/* @var $request \zesk\Router */
	
	$request = $this->request;
	/* @var $request \zesk\Request */
	
	$response = $this->response;
	/* @var $response \zesk\Response_Text_HTML */
}

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
	'class' => $inline ? CSS::add_class($this->class, 'hidden') : $this->class
));

$options = $this->get(array(
	"inline" => $inline,
	"sideBySide" => true,
	"format" => __("Module_Bootstrap_DateTimePicker::widget_layout_format:=YYYY-MM-DD hh:mm a"),
	"toolbarPlacement" => "bottom",
	"showTodayButton" => true
));
$zformat = "{YYYY}-{MM}-{DD} {hh}:{mm}";

$ts_now = Timestamp::now();
$ts_mindate = null;
$ts_maxdate = null;
$ts_defaultdate = null;

if ($this->data_future_only) {
	$ts_mindate = $ts_now;
} else if ($this->data_past_only) {
	$ts_maxdate = $ts_now;
}

if ($inline) {
	/* @var $ts_defaultdate Timestamp */
	$ts_defaultdate = Timestamp::factory(firstarg($this->default, $value));
	$ts_defaultdate = $ts_defaultdate->earlier($ts_maxdate);
	$ts_defaultdate = $ts_defaultdate->later($ts_mindate);
}

foreach (array(
	"minDate" => $ts_mindate,
	"maxDate" => $ts_maxdate,
	"defaultDate" => $ts_defaultdate
) as $option_key => $ts) {
	if ($ts) {
		$options[$option_key] = $ts->format($zformat);
	}
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
$locale = $this->get("locale", Locale::language());

// https://github.com/Eonasdan/bootstrap-datetimepicker/issues/1718
$js_language = "moment.localeData(\"$locale\") ? \"$locale\" : \"en\"";

$options['*locale'] = $js_language;

$original_id = $id;
if ($inline) {
	$id = "$id-dtp";
	echo HTML::div("#$id", "");
}
$jquery = "\$(\"#$id\").datetimepicker(" . JSON::encode($options) . ")";

if ($onchange) {
	$onchange = JavaScript::clean_code($onchange);
	//	$options['*onChangeDateTime'] = "function (dp, \$input) {\n\tvar datetime = new Date(\$input.val());\n\t$onchange;\n}";
}
if ($this->oninit) {
	$oninit = JavaScript::clean_code($this->oninit);
	$jquery .= ".on(\"dp.change\", function (e) {\n\t\tvar datetime = e.date.toDate();\n\t$onchange;\n})";
}
if ($inline) {
	$jquery .= ".on(\"dp.change\", function (e) {\n\t\$(\"#$original_id\").val(e.date.format('YYYY-MM-DD HH:mm:ss'));\n})";
}

$jquery .= ";";

$this->response->jquery($jquery);
