<?php
namespace zesk;

/* @var $application Application */
/* @var $response Response */
$response->jquery();
$response->css('/share/zesk/css/exception.css', array(
	'share' => true
));
$response->javascript('/share/zesk/js/exception.js', array(
	'share' => true
));

echo HTML::div(".exception", $this->content);
