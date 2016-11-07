<?php
if (false) {
	/* @var $response zesk\Response_Text_HTML */
	$response = $this->response;
}

$response->cdn_javascript('/share/zesk/js/zesk.js', array(
	'share' => true,
	'weight' => 'first'
));
$response->cdn_javascript('/share/zesk/js/locale.js', array(
	'share' => true,
	'weight' => -20
));
$response->cdn_javascript('/share/job/job.js', array(
	'share' => true
));
