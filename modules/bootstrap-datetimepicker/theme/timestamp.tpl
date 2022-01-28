<?php declare(strict_types=1);
/**
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $request \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
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

echo $this->theme('zesk/control/text', [
	'onchange' => null,
	'class' => $inline ? CSS::add_class($this->class, 'hidden') : $this->class,
]);

$options = $this->get([
	"inline" => $inline,
	"sideBySide" => true,
	"format" => __("Module_Bootstrap_DateTimePicker::widget_layout_format:=YYYY-MM-DD hh:mm a"),
	"toolbarPlacement" => "bottom",
	"showTodayButton" => true,
]);
$zformat = "{YYYY}-{MM}-{DD} {hh}:{mm}";

$ts_now = Timestamp::now();
$ts_mindate = null;
$ts_maxdate = null;
$ts_defaultdate = null;

if ($this->data_future_only) {
	$ts_mindate = $ts_now;
} elseif ($this->data_past_only) {
	$ts_maxdate = $ts_now;
}

if ($inline) {
	/* @var $ts_defaultdate Timestamp */
	$ts_defaultdate = Timestamp::factory($this->default ?? $value);
	$ts_defaultdate = $ts_defaultdate->earlier($ts_maxdate);
	$ts_defaultdate = $ts_defaultdate->later($ts_mindate);
}

foreach ([
	"minDate" => $ts_mindate,
	"maxDate" => $ts_maxdate,
	"defaultDate" => $ts_defaultdate,
] as $option_key => $ts) {
	if ($ts) {
		$options[$option_key] = $ts->format($locale, $zformat);
	}
}

//	"format_time" => "formatTime",
//	"format_date" => "formatDate",
//	"allow_times" => "allowTimes",
foreach ([
	"step" => "stepping",
] as $template_key => $js_option) {
	if ($this->has($template_key)) {
		$options[$js_option] = $this->get($template_key);
	}
}

$locale_code = $locale->id();
// https://github.com/Eonasdan/bootstrap-datetimepicker/issues/1718
$js_language = "moment.localeData(\"$locale_code\") ? \"$locale_code\" : \"en\"";

$options['*locale'] = $js_language;

$original_id = $id;
if ($inline) {
	$id = "$id-dtp";
	echo HTML::div("#$id", "");
}
$jquery = "\$(\"#$id\").datetimepicker(" . JSON::encodex($options) . ")";

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
