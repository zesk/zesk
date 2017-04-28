<?php
/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \zesk\User */
$parent = $this->parent;
if (!$parent) {
	echo $this->empty_string;
	return;
}
$errors = $parent->errors();
if (count($errors) === 0) {
	$errors = $parent->children_errors();
}
if (count($errors) === 0) {
	echo $this->empty_string;
	return;
}
?><div class="alert alert-error">
	<a class="close" data-dismiss="alert" href="#">&times;</a><?php
	echo $this->theme($application->zesk_root('theme/zesk/view/errors'));
	?></div>
