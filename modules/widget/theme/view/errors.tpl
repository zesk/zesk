<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $request \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */

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
	'class' => CSS::add_class("alert alert-$class"),
), HTML::tags("p", $errors));
