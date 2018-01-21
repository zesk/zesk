<?php
/**
 * $URL$
 * @package ruler
 * @subpackage page
 * @author kent
 * @copyright Copyright &copy; 2009, Market Ruler, LLC
 * Created on Tue Feb 17 20:42:50 EST 2009 20:42:50
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \zesk\User */
$web_key = $this->get("web_key", $zesk->configuration->web_key);

$u = $request->get("u", "/");
$uc = $request->get("uk");
$s = $request->get("s");

$m = false;

if (md5($web_key . $u) !== $uc) {
	$u = "/";
	$m = "Invalid http::redirect URL.";
}
if (strlen($s) === 32) {
	$session = Session_ORM::one_time_find($s);
}
$response->redirect($u, $m);
