<?php declare(strict_types=1);
/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
/* @var $object \zesk\DaemonTools\Service */
namespace zesk;

$ok = $object->optionBool("ok");
$class = implode(" ", [
	"daemontools-service processes",
	$object->status,
	$ok ? "ok" : "not-ok",
]);
echo HTML::tag_open("li", [
	"class" => $class,
]);
echo HTML::tag("strong", ".name", $object->path);
echo HTML::etag("span", ".pid", $object->pid);
echo HTML::span(".status", $object->status);

echo HTML::span(".duration", $this->theme('duration', $object->duration));
echo HTML::tag_close("li");
