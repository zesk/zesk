<?php
/**
 *
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \User */
/* @var $server_data_key string */
/* @var $server_updated_key string */
$servers = $application->orm_registry(Server::class)->query_select()->orm_iterator();
foreach ($servers as $server) {
	/* @var $server \Server */
	$data = $server->data($server_data_key);
	$last_updated = $server->data($server_updated_key);
	if ($last_updated instanceof Timestamp) {
		$updated = $this->theme(array(
			"system/panel/daemon/updated",
			"system/panel/updated",
			"updated",
		), array(
			"content" => $last_updated,
		), array(
			"first" => true,
		));
	} else {
		$updated = $locale->__("never updated");
	}
	$items[] = HTML::tag("li", '.heading', $locale->__("{name} ({updated})", array(
		"name" => $server->name,
		"updated" => $updated,
	)));
	if (!$data) {
		$items[] = HTML::tag('li', '.error', "No process data");
	} else {
		$now = microtime(true);
		foreach ($data as $process => $settings) {
			$items[] = $this->theme("system/panel/daemon/line", array(
				"content" => $settings,
				"process" => $process,
			));
		}
	}
}
echo HTML::tag("ul", implode("\n", $items));
