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
/* @var $this Template */

if ($this->has("URL")) {
	$u = $this->URL;

	$host = url::host($u);
	$current_host = url::current_host();

	$attr = $this->has("Attributes") ? html::parse_attributes($this->Attributes) : array();
	if ($host === $current_host) {
		$out_u = url::query_format($u, array(
			"ref" => url::current()
		));
	} else {
		$session = Session_Database::instance(true);
		$uk = md5($web_key . $u);
		$out_u = url::query_format("/out/", array(
			"u" => $u,
			"uk" => $uk,
			"ref" => url::current()
		));
	}
	$attr['href'] = $out_u;
	if ($this->has("Redirect") && $this->Redirect) {
		$response->redirect($out_u);
	}
	echo html::tag("a", $attr, $this->get("LinkText", $u));
	return;
}

$u = $request->get("u");
$uk = $request->get("uk");
$ref = $request->get("ref", '/');

if (md5($web_key . $u) !== $uk) {
	if ($ref === '') {
		echo "Invalid outbound URL: $u";
	} else {
		$response->redirect($ref, "Invalid outbound URL.");
	}
}

$session = Session_Database::instance(true);
$session = $session->one_time_create();
$in_url = url::query_format(url::left_host($u) . "in/", array(
	"u" => $u,
	"uk" => $uk,
	"s" => $session->member('Cookie')
));

$response->redirect($in_url, $request->get("message"));
