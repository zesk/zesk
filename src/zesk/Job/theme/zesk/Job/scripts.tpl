<?php
declare(strict_types=1);
namespace zesk;

/* @var $response Response */
$response->javascript('/share/zesk/js/zesk.js', [
	'share' => true,
	'weight' => 'first',
]);
$response->javascript('/share/zesk/js/locale.js', [
	'share' => true,
	'weight' => -20,
]);
$response->javascript('/share/job/job.js', [
	'share' => true,
]);
