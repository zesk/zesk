<?php declare(strict_types=1);
namespace zesk;

class Route_Theme extends Route {
	/**
	 * Whether the theme path is set by variables
	 *
	 * @var bool
	 */
	protected bool $dynamic_theme = false;

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Route::initialize()
	 */
	protected function initialize(): void {
		parent::initialize();
		$theme = $this->option('theme');
		if (mapClean($theme) !== $theme) {
			$this->dynamic_theme = true;
		}
	}

	/**
	 * Validate this route
	 *
	 * @throws Exception_File_NotFound
	 * @return bool
	 */
	public function validate(): bool {
		$application = $this->router->application;
		$parameters = $application->variables() + [
			'route' => $this,
		];
		$parameters += $this->options + $this->named;
		$args = map($this->optionArray('theme arguments'), $parameters) + $parameters;
		$theme = $this->option('theme');
		if ($application->themeExists($theme, $args)) {
			return true;
		}

		throw new Exception_File_NotFound('No theme {theme} found in {themePaths}', [
			'theme' => $theme,
			'themePaths' => $application->themePath(),
		]);
	}

	/**
	 * @param Response $response
	 * @return Response
	 * @throws Exception_Redirect
	 */
	public function _execute(Response $response): Response {
		$application = $this->application;
		$parameters = $application->variables() + [
			'route' => $this,
		];
		$parameters += $this->options + $this->named;
		$args = map($this->optionArray('theme arguments'), $parameters) + $parameters;
		$mapped_theme = $theme = $this->option('theme');
		$theme_options = $this->optionArray('theme options');
		if ($this->dynamic_theme) {
			$mapped_theme = map($theme, $parameters);
			if (!$application->themeExists($mapped_theme, $args)) {
				$response->setStatus(HTTP::STATUS_FILE_NOT_FOUND);
				$response->setContent("Theme $mapped_theme not found");
				return $response;
			}
			$application->logger->debug('Executing theme={theme} mapped_theme={mapped_theme} args={args}', compact('theme', 'mapped_theme', 'args'));
		}
		$content = $application->theme($mapped_theme, $args, $theme_options); //TODO
		$response->content = $content;

		$json_html = $this->option('json_html', false);
		if ($json_html && $response->isJSON() || $this->optionBool('json')) {
			$response->json()->setData($response->html()->toJSON() + [
				'status' => true,
			]);
		}
		return $response;
	}
}
