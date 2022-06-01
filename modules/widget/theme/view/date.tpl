<?php declare(strict_types=1);
/**
 *
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $locale \zesk\Locale */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
$is_empty = false;

try {
	$timestamp = new Timestamp($this->value);
	$is_empty = $timestamp->isEmpty();
} catch (Exception_Convert $e) {
	$is_empty = true;
}
if (!$this->object) {
	$this->object = new Model();
}
if ($is_empty) {
	$result = $this->empty_string;
	echo empty($result) ? __('View_Date:=Never.') : $result;
	return true;
}

$format = $this->format;
if (!$format) {
	$format = '{MM}/{DD}/{YYYY} {hh}:{mm}';
}

/* @var $timestamp Timestamp */
$map = [];
$map['delta'] = $locale->now_string($timestamp, $this->get('relative_min_unit', 'second'), $this->zero_string);
$format = map($format, $map);

$result = $timestamp->format($locale, $format);

echo $this->object->applyMap($result);
