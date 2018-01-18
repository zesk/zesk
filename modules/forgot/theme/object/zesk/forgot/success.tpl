<?php
use zesk\HTML;

/**
 * 
 */
if (false) {
	/* @var $this zesk\Template */
	
	$zesk = $this->zesk;
	/* @var $zesk zesk\Kernel */
	
	$application = $this->application;
	/* @var $application \zesk\Application */
	
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
	
	$object = $this->object;
	/* @var $object Forgot */
}
echo HTML::tag('h1', __('Your password has been updated'));
echo HTML::tag('p', StringTools::wrap(__('Please [login] to access your account.'), HTML::a($router->get_route("login"), '[]')));
