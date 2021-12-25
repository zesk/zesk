<?php declare(strict_types=1);
namespace zesk;

/* @var $this Template */
/* @var $application Application */
/* @var $locale Locale */
/* @var $session Session */
/* @var $router Router */
/* @var $route Route */
/* @var $request Request */
/* @var $response Response */
/* @var $current_user User */
/* @var $widget Widget */
/* @var $object ORM */
/* @var $content string */
$options = [
	"title" => $locale->__("Choose your dates"),
	"button" => $locale->__("Choose dates"),
	"value" => $widget->value(),
] + $widget->option_array("dateRangeWidget");
$id = $widget->id();
$json_options = json_encode($options);

$response->html()->jquery("\$(\"#$id\").dateRangeWidget($json_options);");
$response->html()->javascript("/share/bootstrap-datetimepicker-widget/jquery.daterange.js", [
	"share" => true,
]);
$response->html()->css("/share/bootstrap-datetimepicker-widget/jquery.daterange.css", [
	"share" => true,
]);
