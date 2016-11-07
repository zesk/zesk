<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk \zesk\Kernel */
	
	$application = $this->application;
	/* @var $application \zesk\Application */
	
	$session = $this->session;
	/* @var $session \zesk\Session */
	
	$router = $this->router;
	/* @var $request \zesk\Router */
	
	$request = $this->request;
	/* @var $request \zesk\Request */
	
	$response = $this->response;
	/* @var $response \zesk\Response_Text_HTML */
}

$response = $this->response;
if (!$response instanceof Response) {
	$response = $application->response();
}
$messages = $response->redirect_message();
if (count($messages) > 0) {
	$application->modules->object('jGrowl')->ready($response);
	$this->response->jquery("zesk.message(" . JSON::encode(array_values($messages)) . ");");
	$response->redirect_message_clear();
}
