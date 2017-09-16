<?php
/**
 * 
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \User */
/* @var $object zesk\Content_Image */
$title = $object->title;
if (!$title) {
	$title = basename($object->path);
}
$delete_url = $this->router->get_route('delete', $object);
echo HTML::div('.item', HTML::div('.image-picker-item', $object->theme('view', array(
	'width' => 175,
	'height' => 175
))) . HTML::tag('label', $title) . HTML::tag('a', array(
	'class' => 'glyphicon glyphicon-remove action-delete',
	'href' => '/imagepicker/image/' . $object->id() . '/delete',
	'data-ajax' => true
), ''));
