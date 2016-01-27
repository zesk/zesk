<?php
/**
 * $URL$
 * @package ruler
 * @subpackage page
 * @author kent
 * @copyright Copyright &copy; 2009, Market Ruler, LLC
 * Created on Tue Feb 17 20:42:50 EST 2009 20:42:50
 */

/* @var $request Request */
$request = $this->request;
/* @var $response Response */
$response = $this->response;

$web_key = $this->get("web_key", zesk::get('web_key'));

$u = $request->get("u", "/");
$uc = $request->get("uk");
$s = $request->get("s");

$m = false;

if (md5($web_key . $u) !== $uc) {
	$u = "/";
	$m = "Invalid http::redirect URL.";
}
if (strlen($s) === 32) {
	$session = Session_Database::one_time_find($s);
}
$response->redirect($u, $m);
