<?php
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Route_Command extends Route {
	protected function _execute(Response $response) {
		$app = $response->application;
		
		$debug = $this->option_bool('debug');
		
		$command = avalue($this->original_options, "command");
		$args = $this->option_array("arguments", array());
		
		$command = map($command, $this->named);
		$args = map($args, $this->args + $this->named);
		if ($debug) {
			$app->logger->debug("{class}: executing: command={command}, args={args}", array(
				"command" => $command,
				"class" => get_class($this),
				"args" => $args
			));
		}
		$theme_arguments = $this->option_array("theme arguments", array());
		try {
			$result = $app->process->execute_arguments($command, $args);
			if ($debug) {
				$app->logger->debug("{class}: Result is {result}", array(
					"class" => get_class($this),
					"result" => $result
				));
			}
			$content = $app->theme($this->option("theme", "route/command"), array(
				"content" => $result
			) + $theme_arguments);
		} catch (Exception $e) {
			$app->hooks->call("exception", $e);
			$content = $app->theme($this->option("theme", "route/command/empty"), array(
				"content" => $this->option("empty", ""),
				"empty" => true
			) + $theme_arguments);
		}
		$response->content = $content;
	}
}
