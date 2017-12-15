<?php
namespace zesk;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \User */
$servers = $application->orm_registry("zesk\\Server")
	->query_select()
	->order_by("name_internal")
	->object_iterator();

$output_header = false;
/* @var $server zesk\Server */
foreach ($servers as $server) {
	if (!$output_header) {
		echo HTML::tag("div", array(
			"class" => "row header server-status server-status-header"
		), $server->theme("status-row-header"));
		$output_header = true;
	}
	echo HTML::tag("div", array(
		"class" => "row server-status server-status-row",
		"id" => "server-status-" . $server->id()
	), $server->theme("status-row"));
}
