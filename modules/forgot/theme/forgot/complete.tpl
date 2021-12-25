<?php declare(strict_types=1);
/**
 * @version $URL$
 * @author $Author: kent $
 * @package {package}
 * @subpackage {subpackage}
 * @copyright Copyright (C) 2016, {company}. All rights reserved.
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

/* @var $token string */
echo HTML::tag("h1", $locale->__("Password updated"));
$href = $router->get_route("login", Controller_Login::class);

echo HTML::tag("p", HTML::wrap($locale->__("Your password has been updated. Please [login] to continue."), HTML::tag("a", [
	"class" => "forgot-index-link",
	"href" => $href,
], '[]')));
