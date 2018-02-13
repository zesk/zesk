<?php
/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \zesk\User */
/* @var $object \zesk\DaemonTools\Service */
namespace zesk;

$ok = $object->option_bool("ok");
$class = implode(" ", array(
	"daemontools-service",
	$object->status,
	$ok ? "ok" : "not-ok"
));
echo HTML::tag_open("li", array(
	"class" => $class
));
echo HTML::tag("strong", ".name", $object->path);
echo HTML::etag("span", ".pid", $object->pid);
echo HTML::span(".status", $object->status);

$duration = $object->duration;
$use_unit = Timestamp::UNIT_SECOND;
$prefix = "";
foreach (Timestamp::$UNITS_TRANSLATION_TABLE as $unit => $seconds) {
	if ($duration > $seconds * 2) {
		$use_unit = $unit;
		$duration = floor($duration / $seconds);
		$prefix = "~";
		break;
	}
}
echo HTML::span(".duration", $locale->__("{prefix}{n} {units}", array(
	"prefix" => $prefix,
	"n" => $duration,
	"units" => $locale->plural($locale->__($unit), $duration)
)));
echo HTML::tag_close("li");

