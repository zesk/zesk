<?php
namespace zesk;

/* @var $this Template */
/* @var $zesk Kernel */
/* @var $application TimeBank */
/* @var $request Request */
/* @var $response Response_Text_HTML */
$result = array();

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

$result = $zesk->hooks->call_arguments('system/status', array(
	$result
), $result);

$request = $this->request;
$response = $this->response;

/* @var $request Request */
$query_string = to_list($request->query(), null);
if ($query_string) {
	$result = arr::filter($result, $query_string);
}
$result['elpased'] = microtime(true) - $zesk->initialization_time;
$response->json($result);
