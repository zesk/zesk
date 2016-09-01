<?php
if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk zesk\Kernel */
	
	$application = $this->application;
	/* @var $application Application */
	
	$session = $this->session;
	/* @var $session Session */
	
	$router = $this->router;
	/* @var $request Router */
	
	$request = $this->request;
	/* @var $request Request */
	
	$response = $this->response;
	/* @var $response Response_HTML */
	
	$current_user = $this->current_user;
	/* @var $current_user User */
}
if (!$response instanceof Response) {
	$response = $application->response();
}
$messages = $response->redirect_message();
if (count($messages) > 0) {
	echo html::tag('div', '.messages alert alert-info', '<a class="close" data-dismiss="alert" href="#">&times;</a>' . html::tag('ul', html::tags('li', $messages)));
	$response->redirect_message_clear();
	$msec = $application->option_integer("messages_timeout_milliseconds", 4000);
	$this->response->jquery("setTimeout(function () {\n\t$('.messages').fadeOut('slow');\n}, $msec);");
}
