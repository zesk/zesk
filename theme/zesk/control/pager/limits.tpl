<?php
/**
 * 
 */
use zesk\HTML;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \User */
/* @var $widget \Control_Select */

$widget = $this->widget;

/* @var $object Model */
$object = $this->object;

/* @var $limit_widget Widget */
$limit_widget = $widget->child('limit');
if (!$limit_widget) {
	return;
}
if (!$limit_widget->is_visible()) {
	return;
}
echo HTML::tag("div", array(
	"class" => 'pager-limits form-group'
), HTML::tag("label", false, __('Control_Pager:=Show') . $limit_widget->render()));
