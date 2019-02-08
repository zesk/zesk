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
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \User */
/* @var $object \Content_Link */
$url_key = $application->configuration->url_key;

$class = array(
	"link",
);
if ($this->class) {
	$class[] = $this->class;
}
$rd_link = URL::query_format("/out", array(
	"link" => $object->id(),
	"url" => $object->URL,
	"key" => md5($object->URL . $url_key),
));

/* @var $object Link */
echo HTML::div_open(array(
	"class" => implode(" ", $class),
));
echo HTML::etag("a", array(
	"href" => $rd_link,
	"onmouseover" => "window.status='" . $object->URL . "'",
	"onmouseout" => "window.status=''",
), $object->image());

echo HTML::a_condition($rd_link === $request->path(), $rd_link, array(
	"class" => "title",
	"onmouseover" => "window.status='" . $object->URL . "'",
	"onmouseout" => "window.status=''",
), $object->Name);

echo $this->theme('control/admin-edit');

echo HTML::etag("p", array(
	"class" => "desc",
), $object->Body);

echo HTML::div_close();
