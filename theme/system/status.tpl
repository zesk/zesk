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
$result = [];

$result['time'] = microtime(true);
$result['date'] = gmdate('Y-m-d H:i:s');
$result['uname_system'] = php_uname('s');
$result['uname_machine'] = php_uname('m');
$result['uname_release'] = php_uname('r');
$result['uname_version'] = php_uname('v');
$result['uname_host'] = php_uname('n');
$result['php_version'] = PHP_VERSION;
$result['php_version_id'] = PHP_VERSION_ID;

$result['System::uname'] = System::uname();
$result['System::load_averages'] = System::load_averages();
$result['System::host_id'] = System::host_id();
$result['System::volume_info'] = System::volume_info();

$result = $application->hooks->call_arguments('system/status', [
	$result,
], $result);

$request = $this->request;
$response = $this->response;

/* @var $request Request */
$query_string = to_list($request->query(), null);
if ($query_string) {
	$result = ArrayTools::filter($result, $query_string);
}
$result['elpased'] = microtime(true) - $application->initializationTime();
$response->json()->data($result);
