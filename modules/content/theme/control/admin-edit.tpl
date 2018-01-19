<?php

/**
 *
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_HTML */
/* @var $current_user \User */
$object = $this->object;

if ($this->user && $this->user->can($object, "edit")) {
	$url = $this->router->get_route('edit', $object);
	if ($url) {
		echo HTML::div(".admin-edit", HTML::tag('a', array(
			'href' => $this->request->get_route('edit', $object)
		), HTML::img($application, '/share/images/actions/edit.gif', __("Edit \"{0}\"", $object->Title))));
	} else {
		echo HTML::div(".admin-edit", HTML::tag('a', array(
			'href' => '#'
		), HTML::img($application, '/share/images/actions/owl.gif', __("Editing is misconfigured \"{0}\"", $object->Title))));
	}
}
