<?php

/* @var $application Application */
$application = $this->application;

/* @var $response Response */
$response = $this->response;

if (!$response instanceof Response) {
	$response = $application->response();
}
$messages = $response->redirect_message();
if (count($messages) > 0) {
	echo html::tag('div', '.messages alert alert-info', '<a class="close" data-dismiss="alert" href="#">&times;</a>' . html::tag('ul', html::tags('li', $messages)));
	$response->redirect_message_clear();
	$msec = zesk::get("messages_timeout_milliseconds", 4000);
	$this->response->jquery("setTimeout(function () {\n\t$('.messages').fadeOut('slow');\n}, $msec);");
}
