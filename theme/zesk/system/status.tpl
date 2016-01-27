<?php
$result = array();

$result['uname'] = system::uname();
$result['time'] = time();
$result['date'] = gmdate('Y-m-d H:i:s');
$result['load'] = system::load_averages();
$result['host_id'] = system::host_id();
$result['volume_info'] = system::volume_info();

$result = zesk::hook('system/status', $result);

$request = $this->request;
$response = $this->response;

/* @var $request Request */
$query_string = to_list($request->query(), null);
if ($query_string) {
	$result = arr::filter($result, $query_string);
}

$response->json($result);
