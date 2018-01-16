<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $request \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
$id = $this->id;
if (empty($id)) {
	$this->id = $id = "datetimepicker-" . $response->id_counter();
}

$zformat = $this->get('format', '{YYYY}-{MM}-{DD}');
$value = Timestamp::factory($value)->date()->format($zformat);

$inline = $this->getb("inline");

echo $this->theme('zesk/control/text', array(
	'value' => $value,
	'onchange' => null,
	'class' => $inline ? CSS::add_class($this->class, 'hidden') : $this->class
));

$options = $this->get(array(
	"inline" => $this->getb('inline'),
	"format" => "YYYY-MM-DD",
	"toolbarPlacement" => "bottom",
	"showTodayButton" => true
));

/* @var $defaultDate Timestamp */
/* @var $minDate Timestamp */
/* @var $maxDate Timestamp */
$defaultDate = $minDate = $maxDate = null;
if ($this->data_future_only) {
	$minDate = Timestamp::now();
} else if ($this->data_past_only) {
	$maxDate = Timestamp::now();
}
if ($inline) {
	if ($this->value) {
		$defaultDate = Timestamp::factory($this->value);
	}
}

if ($defaultDate) {
	if ($minDate) {
		$defaultDate = $defaultDate->later($minDate);
	}
	if ($maxDate) {
		$defaultDate = $defaultDate->earlier($maxDate);
	}
	$options['defaultDate'] = $defaultDate->format($zformat);
}
if ($minDate) {
	$options['minDate'] = $minDate->format($zformat);
}
if ($maxDate) {
	$options['maxDate'] = $maxDate->format($zformat);
}
$locale = $this->get("locale", $locale->language());

// https://github.com/Eonasdan/bootstrap-datetimepicker/issues/1718
$js_language = "moment.localeData(\"$locale\") ? \"$locale\" : \"en\"";

$options['*locale'] = $js_language;
// $options['icons'] = array(
//	'today' => 'glyphicon glyphicon-home'
// );
$original_id = $id;
if ($inline) {
	$id = "$id-dtp";
	echo HTML::div("#$id", "");
}
$jquery = "\$(\"#$id\").datetimepicker(" . JSON::encode($options) . ")";

if ($inline) {
	$jquery .= ".on(\"dp.change\", function (e) {\n\t\$(\"#$original_id\").val(e.date.format('YYYY-MM-DD'));\n})";
}
$jquery .= ";";

$this->response->jquery($jquery);

