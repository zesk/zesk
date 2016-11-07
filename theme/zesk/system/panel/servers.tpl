<?php
use zesk\HTML;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \User */
$servers = $application->query_select("Server")->order_by("name_internal")->object_iterator();
/* @var $server zesk\Server */
foreach ($servers as $server) {
	echo HTML::tag("div", array(
		"class" => "row",
		"id" => "server-status-" . $server->id()
	), $server->theme("status-row"));
}
