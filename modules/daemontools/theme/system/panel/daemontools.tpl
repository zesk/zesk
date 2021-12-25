<?php declare(strict_types=1);
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

/* @var $module \zesk\DaemonTools\Module */
$module = $application->daemontools_module();

/* @var $servers \zesk\Server[] */
$servers = $application->orm_registry(Server::class)->query_select()->orm_iterator();
foreach ($servers as $server) {
	$last_updated = $module->server_services_last_updated($server);
	if ($last_updated instanceof Timestamp) {
		$updated = $this->theme([
			"system/panel/daemontools/updated",
			"system/panel/updated",
			"updated",
		], [
			"content" => $last_updated,
		], [
			"first" => true,
		]);
	} else {
		$updated = $locale->__("never updated");
	}
	$items[] = HTML::tag("li", '.heading', $locale->__("{name} ({updated})", [
		"name" => $server->name,
		"updated" => $updated,
	]));
	$services = $module->server_services($server);
	if ($services === null) {
		$items[] = HTML::tag('li', '.error', "No service data");
	} else {
		if (count($services) === 0) {
			$items[] = HTML::tag('li', '.error', "No services");
		} else {
			foreach ($services as $service) {
				$items[] = $service->theme("system");
			}
		}
	}
}
echo HTML::tag("ul", implode("\n", $items));
