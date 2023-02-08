<?php
declare(strict_types=1);

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Route_Command extends Route {
	/**
	 * The CLI command to run
	 */
	public const OPTION_COMMAND = 'command';

	/**
	 * The CLI arguments to run
	 */
	public const OPTION_ARGUMENTS = 'arguments';

	/**
	 * The Name of the theme to output the command results
	 */
	public const OPTION_THEME = 'theme';

	/**
	 * Optional theme for when the command fails
	 */
	public const OPTION_FAILED_THEME = 'failedTheme';

	/**
	 * Variables passed to the themes when called (array value)
	 */
	public const OPTION_THEME_ARGUMENTS = 'theme arguments';

	/**
	 *
	 * @var string
	 * @zesk theme
	 */
	public const DEFAULT_OPTION_THEME = 'route/command';

	/**
	 *
	 * @var string
	 * @zesk theme
	 */
	public const DEFAULT_OPTION_FAILED_THEME = 'route/command/failed';

	protected function _execute(Request $request, Response $response): Response {
		$app = $response->application;

		$debug = $this->optionBool(self::OPTION_DEBUG);

		$command = $this->original_options[self::OPTION_COMMAND] ?? '';
		$args = $this->optionArray(self::OPTION_ARGUMENTS);

		$command = map($command, $this->named);
		$args = map($args, $this->args + $this->named);
		if ($debug) {
			$app->logger->debug('{class}: executing: command={command}, args={args}', [
				'command' => $command, 'class' => get_class($this), 'args' => $args,
			]);
		}
		$theme_arguments = $this->optionArray(self::OPTION_THEME_ARGUMENTS);

		try {
			$result = $app->process->executeArguments($command, $args);
			if ($debug) {
				$app->logger->debug('{class}: Result is {result}', [
					'class' => get_class($this), 'result' => $result,
				]);
			}
			$resultTheme = $this->option(self::OPTION_THEME, self::DEFAULT_OPTION_THEME);
			$content = $app->themes->theme($resultTheme, [
				'content' => $result, 'failed' => false, 'exitCode' => 0,
			] + $theme_arguments);
		} catch (Exception_Command $e) {
			$app->hooks->call('exception', $e);
			$failedTheme = $this->option(self::OPTION_FAILED_THEME, self::DEFAULT_OPTION_FAILED_THEME);
			$content = $app->themes->theme($failedTheme, [
				'content' => $e->getOutput(), 'failed' => true,
			] + $e->variables() + $theme_arguments);
		}
		return $response->setContent($content);
	}
}
