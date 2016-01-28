<?php

/* @var $response Response_HTML */
if (false) {
	$response = $this->response;
}

$response->javascript('/share/icalendar/js/rrule.js', array(
	'share' => true
));

$child_widgets = $this->child_widgets;

echo html::div_open('.control-rrule');

echo html::div('.widget-repeat', $child_widgets['repeat']->render());

echo html::div_close();