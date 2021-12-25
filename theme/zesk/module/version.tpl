<?php declare(strict_types=1);
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
/* @var $object \zesk\Module */
if ($object instanceof Module) {
	$version = $object->version();
	if (!$version) {
		return;
	}
	echo $locale->__("{name} (<code>{codename}</code>) version {version}", [
		"name" => $object->name(),
		"codename" => $object->codename(),
		"version" => $version,
	]);
}
