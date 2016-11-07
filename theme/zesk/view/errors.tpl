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


/* @var $widget View_Errors */
/* @var $parent Widget */
$errors = array();
$parent = $this->parent;

if ($parent) {
	$errors = $parent->errors();
	if (count($errors) === 0) {
		$errors = $parent->children_errors();
	}
}
$value = $this->object->get($this->column);
if (is_array($value)) {
	$errors = array_merge($errors, $value);
}
if (count($errors) === 0) {
	echo $this->empty_string;
	return;
}

if (avalue($errors, "continue", false)) {
	unset($errors["continue"]);
	$label = $this->get('continue_label', '');
	$class = $this->get('continue_class', "continue");
} else {
	$label = $this->get('label', __("Please fix the following:"));
	$class = 'danger';
}

echo HTML::etag("div", array(
	'class' => CSS::add_class("alert alert-$class")
), HTML::tags("p", $errors));
