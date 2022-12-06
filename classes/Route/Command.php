<?php declare(strict_types=1);
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Route_Command extends Route {
	protected function _execute(Response $response): void {
		$app = $response->application;

		$debug = $this->optionBool('debug');

		$command = avalue($this->original_options, 'command');
		$args = $this->optionArray('arguments', []);

		$command = map($command, $this->named);
		$args = map($args, $this->args + $this->named);
		if ($debug) {
			$app->logger->debug('{class}: executing: command={command}, args={args}', [
				'command' => $command,
				'class' => get_class($this),
				'args' => $args,
			]);
		}
		$theme_arguments = $this->optionArray('theme arguments', []);

		try {
			$result = $app->process->executeArguments($command, $args);
			if ($debug) {
				$app->logger->debug('{class}: Result is {result}', [
					'class' => get_class($this),
					'result' => $result,
				]);
			}
			$content = $app->theme($this->option('theme', 'route/command'), [
				'content' => $result,
			] + $theme_arguments);
		} catch (Exception $e) {
			$app->hooks->call('exception', $e);
			$content = $app->theme($this->option('theme', 'route/command/empty'), [
				'content' => $this->option('empty', ''),
				'empty' => true,
			] + $theme_arguments);
		}
		$response->content = $content;
	}
}
