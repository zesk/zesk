<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this Template */
/* @var $zesk Kernel */
/* @var $application Application */
/* @var $session Session */
/* @var $request Router */
/* @var $request Request */
/* @var $response Response */
$messages = $response->redirect_message();
if (count($messages) > 0) {
	$application->modules->object('jGrowl')->ready($response);
	$this->response->jquery("zesk.message(" . JSON::encode(array_values($messages)) . ");");
	$response->redirect_message_clear();
}
