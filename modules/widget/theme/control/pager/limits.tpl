<?php declare(strict_types=1);
/**
 *
 */
use zesk\HTML;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
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
$limit_widget->addClass('pager-limit-widget');
echo HTML::tag('div', [
	'class' => 'pager-limits btn-group',
], HTML::tag('a', '.btn disabled pager-limits-label pager-text btn-sm', __('Control_Pager:=Show')) . $limit_widget->render());
