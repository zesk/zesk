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
/* @var $object \Content_File */
echo HTML::tag_open('div', [
	'class' => CSS::addClass('file', $this->class),
]);
// TODO Fix this
$uri = URL::queryAppend('/download.php', [
	'FileGroup' => $object->Parent,
	'ID' => $object->ID,
]);
echo HTML::a_condition($uri === $request->path(), $uri, [
	'class' => 'title',
], $object->Name);
echo $this->theme('control/admin-edit');
echo HTML::etag('p', [
	'class' => 'desc',
], $object->Body);
echo HTML::tag_close('div');
