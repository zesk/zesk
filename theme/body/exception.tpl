<?php
namespace zesk;

/* @var $application Application */
/* @var $response Response_Text_HTML */
$response = $this->response;
if (!$response) {
	$response = $application->response();
}
$response->jquery();
$response->css('/share/zesk/css/exception.css', array(
	'share' => true
));
$response->javascript('/share/zesk/js/exception.js', array(
	'share' => true
));

echo HTML::div(".exception", $this->content);
