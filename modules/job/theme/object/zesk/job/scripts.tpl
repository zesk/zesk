<?php
namespace zesk;

/* @var $response Response_Text_HTML */

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
