<?php
/**
 * 
 */
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
/* @var $server_data_key string */
$servers = $application->query_select("zesk\\Server")->object_iterator();
foreach ($servers as $server) {
	/* @var $server \Server */
	$data = $server->data($server_data_key);
	$items[] = HTML::tag("li", '.heading', $server->name);
	if (!$data) {
		$items[] = HTML::tag('li', '.error', "No process data");
	} else {
		$now = microtime(true);
		foreach ($data as $process => $settings) {
			$nunits = intval($now - $settings['time']);
			$units = Locale::plural(__("second"), $nunits);
			$class = $settings['alive'] ? "" : '.error';
			if ($process === "me") {
				$process = __('Master Daemon Process');
			} else {
				$process = preg_replace_callback('#\^([0-9]+)#', function ($match) {
					return " (#" . (intval($match[1]) + 1) . ")";
				}, $process);
			}
			$items[] = HTML::tag('li', $class, _W(__("[{process}] {status} for {nunits} {units}", array(
				"process" => $process,
				"status" => $settings['status'],
				"nunits" => $nunits,
				"units" => $units
			)), HTML::tag("strong", "[]")));
		}
	}
}
echo HTML::tag("ul", implode("\n", $items));