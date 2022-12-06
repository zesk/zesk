<?php declare(strict_types=1);
/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/* @var $this Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $request \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
$id = $this->id;
if (empty($id)) {
	$this->id = $id = 'datetimepicker-' . $response->id_counter();
}

$zformat = $this->get('format', '{YYYY}-{MM}-{DD}');
$value = Timestamp::factory($value)->date()->format($locale, $zformat);

$inline = $this->getBool('inline');

echo $this->theme('zesk/control/text', [
	'value' => $value,
	'onchange' => null,
	'class' => $inline ? CSS::addClass($this->class, 'hidden') : $this->class,
]);

$options = $this->get([
	'inline' => $this->getBool('inline'),
	'format' => 'YYYY-MM-DD',
	'toolbarPlacement' => 'bottom',
	'showTodayButton' => true,
]);

/* @var $defaultDate Timestamp */
/* @var $minDate Timestamp */
/* @var $maxDate Timestamp */
$defaultDate = $minDate = $maxDate = null;
if ($this->data_future_only) {
	$minDate = Timestamp::now();
} elseif ($this->data_past_only) {
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
	$options['defaultDate'] = $defaultDate->format($locale, $zformat);
}
if ($minDate) {
	$options['minDate'] = $minDate->format($locale, $zformat);
}
if ($maxDate) {
	$options['maxDate'] = $maxDate->format($locale, $zformat);
}
$locale = $locale->language();

// https://github.com/Eonasdan/bootstrap-datetimepicker/issues/1718
$js_language = "moment.localeData(\"$locale\") ? \"$locale\" : \"en\"";

$options['*locale'] = $js_language;
// $options['icons'] = array(
//	'today' => 'glyphicon glyphicon-home'
// );
$original_id = $id;
if ($inline) {
	$id = "$id-dtp";
	echo HTML::div("#$id", '');
}
$jquery = "\$(\"#$id\").datetimepicker(" . JSON::encodeJavaScript($options) . ')';

if ($inline) {
	$jquery .= ".on(\"dp.change\", function (e) {\n\t\$(\"#$original_id\").val(e.date.format('YYYY-MM-DD'));\n})";
}
$jquery .= ';';

$this->response->jquery($jquery);
