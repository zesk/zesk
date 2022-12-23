<?php declare(strict_types=1);
namespace zesk;

/* @var $application Application */
/* @var $response Response */
if ($response) {
	$response->jquery();
	$response->css('/share/zesk/css/exception.css', [
		'share' => true,
	]);
	$response->javascript('/share/zesk/js/exception.js', [
		'share' => true,
	]);
}
echo HTML::div('.exception', $this->content);
