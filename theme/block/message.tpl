<?php
namespace zesk;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \zesk\User */
if (!$response instanceof Response) {
	$response = $application->response();
}
$messages = $response->redirect_message();
if (count($messages) > 0) {
	echo HTML::tag('div', '.messages alert alert-info', '<a class="close" data-dismiss="alert" href="#">&times;</a>' . HTML::tag('ul', HTML::tags('li', $messages)));
	$response->redirect_message_clear();
	$msec = $application->option_integer("messages_timeout_milliseconds", 4000);
	$this->response->jquery("setTimeout(function () {\n\t$('.messages').fadeOut('slow');\n}, $msec);");
}
