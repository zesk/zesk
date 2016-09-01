<?php

use zesk\Timestamp as Timestamp;

/* @var $this Template */
$response = $this->response;
/* @var $response Response_HTML */

$id = $this->id;
if (empty($id)) {
	$this->id = $id = "datetimepicker-" . $response->id_counter();
}

$value = Timestamp::factory($value)->date()->format($this->get('format', '{YYYY}-{MM}-{DD}'));
echo $this->theme('control/text', array(
	'value' => $value
));

$options = $this->get(array(
	"lang" => zesk\Locale::language(zesk\Locale::current()),
	"inline" => $this->getb('inline'),
	"format" => "Y-m-d",
	'timepicker' => false
));
if ($this->data_future_only) {
	$options['minDate'] = Timestamp::now()->format();
} else if ($this->data_past_only) {
	$options['maxDate'] = Timestamp::now()->format();
}

$this->response->jquery("\$(\"#$id\").datetimepicker(" . json::encode($options) . ");");
