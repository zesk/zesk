<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \User */
/* @var $object Content_Article */
if (empty($object->Byline)) {
	return;
}

$posted_vars = [
	'byline' => $object->Byline,
	'date' => Timestamp::factory($object->Created)->format($locale, '{mmmm} {ddd}, {yyyy} {12HH}:{MM} {AMPM}'),
];
echo HTML::tag('div', [
	'class' => 'byline',
], $locale->__('Content_Article:=Posted by {byline} on {date}', $posted_vars));
