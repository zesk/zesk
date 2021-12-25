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

$response->javascript('/share/icalendar/js/rrule.js', [
	'share' => true,
]);

$child_widgets = $this->child_widgets;

echo HTML::div_open('.control-rrule');

echo HTML::div('.widget-repeat', $child_widgets['repeat']->render());

echo HTML::div_close();
