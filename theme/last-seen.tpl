<?php
/**
 * @package zesk
 * @subpackage theme
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
if (!$this->content) {
	echo $locale->__('Never logged in');
	return;
}

$content = $this->content instanceof Timestamp ? $this->content : new Timestamp($this->content);

$now = Timestamp::now();

$strings = array(
	'year' => 'Last seen {YYYY}',
	'month' => 'Last seen {MMMM} {YYYY}',
	'week' => 'Last seen {n} {units} ago',
	'day' => 'Last seen {n} {units} ago',
	'hour' => 'Visited today, {n} {units} ago',
	'minute' => 'Visited recently, {n} {units} ago'
);
foreach ($strings as $unit => $format) {
	if (($n = $now->difference($content, $unit)) > 0) {
		$map['n'] = $n;
		$map['units'] = $locale->plural($unit, $n);
		$format = $locale->__($format, $map);
		echo $content->format($locale, $format);
		return;
	}
}
echo HTML::span(".currently-online", $locale('Currently online'));
