<?php declare(strict_types=1);
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
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
/* @var $object \Content_Article */
$this->response->title($object->Title);

$byline = $object->Byline;

echo HTML::tag_open('div', ".article article-view " . $object->class_code_name());
echo $this->theme('control/admin-edit');

echo HTML::tag("h1", $object->title);

echo HTML::tag_open('div', '.article-entry cmhtml');
echo $object->body;

echo HTML::tag_close('div');
echo HTML::tag_close('div');
