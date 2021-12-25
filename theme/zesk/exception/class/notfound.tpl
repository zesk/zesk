<?php declare(strict_types=1);

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_HTML */
/* @var $current_user \User */
namespace zesk;

echo $this->theme("exception", [
	"suffix" => HTML::tag("pre", _dump($application->autoloader->path())),
]);
