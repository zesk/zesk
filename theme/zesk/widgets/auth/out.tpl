<?php declare(strict_types=1);
/**
 * @package ruler
 * @subpackage page
 * @author kent
 * @copyright Copyright &copy; 2009, Market Ruler, LLC
 * Created on Tue Feb 17 20:42:50 EST 2009 20:42:50
 */
use zesk\URL;
use zesk\HTML;
use zesk\Session_ORM;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Interface_Session */
/* @var $request \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user User */
$web_key = $this->get('web_key', $application->option('web_key'));
/* @var $this zesk\Template */

if ($this->has('URL')) {
	$u = $this->URL;

	$host = URL::host($u);
	$current_host = $request->host();

	$attr = $this->has('Attributes') ? HTML::parse_attributes($this->Attributes) : [];
	if ($host === $current_host) {
		$out_u = URL::query_format($u, [
			'ref' => $this->request->url(),
		]);
	} else {
		$session = Session_ORM::instance(true);
		$uk = md5($web_key . $u);
		$out_u = URL::query_format('/out/', [
			'u' => $u,
			'uk' => $uk,
			'ref' => $this->request->url(),
		]);
	}
	$attr['href'] = $out_u;
	if ($this->has('Redirect') && $this->Redirect) {
		$response->redirect($out_u);
	}
	echo HTML::tag('a', $attr, $this->get('LinkText', $u));
	return;
}

$u = $request->get('u');
$uk = $request->get('uk');
$ref = $request->get('ref', '/');

if (md5($web_key . $u) !== $uk) {
	if ($ref === '') {
		echo "Invalid outbound URL: $u";
	} else {
		$response->redirect($ref, 'Invalid outbound URL.');
	}
}

$session = Session_ORM::instance(true);
$session = $session->one_time_create();
$in_url = URL::query_format(URL::left_host($u) . 'in/', [
	'u' => $u,
	'uk' => $uk,
	's' => $session->member('Cookie'),
]);

$response->redirect($in_url, $request->get('message'));
