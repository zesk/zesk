<?php

/* @var $this Template */
$response = $this->response;
/* @var $response Response_HTML */

$id = $this->id;
if (empty($id)) {
	$this->id = $id = "datetimepicker-" . $response->id_counter();
}

$zformat = $this->get('format', '{YYYY}-{MM}-{DD}');
$value = Timestamp::factory($value)->date()->format($zformat);

$inline = $this->getb("inline");

echo $this->theme('control/text', array(
	'value' => $value,
	'onchange' => null,
	'class' => $inline ? css::add_class($this->class, 'hidden') : $this->class
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
$options['locale'] = $this->get("locale", zesk\Locale::language());
// $options['icons'] = array(
//	'today' => 'glyphicon glyphicon-home'
// );
$original_id = $id;
if ($inline) {
	$id = "$id-dtp";
	echo html::div("#$id", "");
}
$jquery = "\$(\"#$id\").datetimepicker(" . json::encode($options) . ")";

if ($inline) {
	$jquery .= ".on(\"dp.change\", function (e) {\n\t\$(\"#$original_id\").val(e.date.format('YYYY-MM-DD'));\n})";
}
$jquery .= ";";

$this->response->jquery($jquery);