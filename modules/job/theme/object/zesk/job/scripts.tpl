<?php
namespace zesk;

/* @var $response Response */
$response->javascript('/share/zesk/js/zesk.js', array(
	'share' => true,
	'weight' => 'first',
));
$response->javascript('/share/zesk/js/locale.js', array(
	'share' => true,
	'weight' => -20,
));
$response->javascript('/share/job/job.js', array(
	'share' => true,
));
