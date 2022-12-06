<?php declare(strict_types=1);
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $object \zesk\Contact_Address */
if (!$this->from instanceof Contact_Address) {
	return;
}
$distance = $object->distance($this->from);
if ($distance !== null) {
	?><span class="contact-address-distance"><?php
	echo $this->theme('distance', [
		'content' => $distance,
		'units' => 'miles',
	]); ?></span><?php
}
