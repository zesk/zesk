<?php
if (false) {
	/* @var $this zesk\Template */
	
	$zesk = $this->zesk;
	/* @var $zesk zesk\Kernel */
	
	$application = $this->application;
	/* @var $application TimeBank */
	
	$session = $this->session;
	/* @var $session Session */
	
	$router = $this->router;
	/* @var $request Router */
	
	$request = $this->request;
	/* @var $request Request */
	
	$response = $this->response;
	/* @var $response zesk\Response_Text_HTML */
	
	$current_user = $this->current_user;
	/* @var $current_user User */
}

/* @var $this zesk\Template */

$styles = <<<EOF
div.redirect, div.backtrace {
	border: 2px solid black;
	padding: 20px;
	color: black;
	background-color: white;
}
div.redirect {
}
div.redirect label {
	float: left;
	padding-right: 10px;
}
div.redirect a {
	color: blue;
	font-weight: bold;
}
EOF;
$response->css_inline($styles);

$this->begin('response/html.tpl');
$this->begin('body/exception.tpl');

echo HTML::div('.redirect', HTML::tag('label', __('Redirect:')) . $this->content);

if ($zesk->configuration->path_get('Response::redirect_show_backtrace', $this->application->development())) {
	echo HTML::div('.backtrace', $this->theme('exception/trace', array(
		'content' => debug_backtrace(false)
	)));
}
echo $this->end();
echo $this->end();
