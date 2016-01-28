<?php

/* @var $response Response */
$response = $this->response;
if (!$response instanceof Response) {
	$response = zesk::singleton("Response");
}
$messages = $response->redirect_message();
if (count($messages) > 0) {
	Module::object('jGrowl')->ready($response);
	$this->response->jquery("zesk.message(" . json::encode(array_values($messages)) . ");");
	$response->redirect_message_clear();
}
