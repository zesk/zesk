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
/* @var $object \Content_Link */
$url_key = $application->configuration->url_key;

$class = [
	'link',
];
if ($this->class) {
	$class[] = $this->class;
}
$rd_link = URL::query_format('/out', [
	'link' => $object->id(),
	'url' => $object->URL,
	'key' => md5($object->URL . $url_key),
]);

/* @var $object Link */
echo HTML::div_open([
	'class' => implode(' ', $class),
]);
echo HTML::etag('a', [
	'href' => $rd_link,
	'onmouseover' => 'window.status=\'' . $object->URL . '\'',
	'onmouseout' => 'window.status=\'\'',
], $object->image());

echo HTML::a_condition($rd_link === $request->path(), $rd_link, [
	'class' => 'title',
	'onmouseover' => 'window.status=\'' . $object->URL . '\'',
	'onmouseout' => 'window.status=\'\'',
], $object->Name);

echo $this->theme('control/admin-edit');

echo HTML::etag('p', [
	'class' => 'desc',
], $object->Body);

echo HTML::div_close();
