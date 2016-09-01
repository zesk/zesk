<?php
class Route_Command extends Route {

	protected function _execute(Application $app) {
		$response = $app->response;
		$debug = $this->option_bool('debug');

		$command = avalue($this->original_options, "command");
		$args = $this->option_array("arguments", array());

		$command = map($command, $this->named);
		$args = map($args, $this->args + $this->named);
		if ($debug) {
			log::debug("{class}: executing: command={command}, args={args}", array(
				"command" => $command,
				"class" => get_class($this),
				"args" => $args
			));
		}
		$theme_arguments = $this->option_array("theme arguments", array());
		try {
			$result = zesk::execute_array($command, $args);
			if ($debug) {
				log::debug("{class}: Result is {result}", array(
					"class" => get_class($this),
					"result" => $result
				));
			}
			$content = theme($this->option("theme", "route/command"), array(
				"content" => $result
			) + $theme_arguments);
		} catch (Exception $e) {
			global $zesk;
			$zesk->hooks->call("exception", $e);
			$content = theme($this->option("theme", "route/command/empty"), array(
				"content" => $this->option("empty", ""),
				"empty" => true
			) + $theme_arguments);
		}
		$response->content = $content;
	}
}
