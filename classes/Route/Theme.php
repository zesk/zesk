<?php declare(strict_types=1);
namespace zesk;

class Route_Theme extends Route {
	/**
	 * Whether the theme path is set by variables
	 *
	 * @var unknown
	 */
	protected $dynamic_theme = false;

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Route::initialize()
	 */
	protected function initialize(): void {
		parent::initialize();
		$theme = $this->option('theme');
		if (map_clean($theme) !== $theme) {
			$this->dynamic_theme = true;
			return;
		}
	}

	/**
	 * Validate this route
	 *
	 * @throws Exception_File_NotFound
	 * @return Route_Theme
	 */
	public function validate(): bool {
		$application = $this->router->application;
		$parameters = $application->variables() + [
			'route' => $this,
		];
		$parameters += $this->options + $this->named;
		$args = map($this->optionArray('theme arguments', []), $parameters) + $parameters;
		$theme_options = $this->optionArray('theme options');
		$theme = $this->option('theme');
		if ($application->theme_exists($theme, $args, $theme_options)) {
			return $this;
		}

		throw new Exception_File_NotFound('No theme {theme} found in {theme_paths}', [
			'theme' => $theme,
			'theme_paths' => $application->theme_path(),
		]);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Route::_execute()
	 */
	public function _execute(Response $response): void {
		$application = $this->router->application;
		$parameters = $application->variables() + [
			'route' => $this,
		];
		$parameters += $this->options + $this->named;
		$args = map($this->optionArray('theme arguments', []), $parameters) + $parameters;
		$mapped_theme = $theme = $this->option('theme');
		$theme_options = $this->optionArray('theme options');
		if ($this->dynamic_theme) {
			$mapped_theme = map($theme, $parameters);
			if (!$application->theme_exists($mapped_theme, $args, $theme_options)) { //TODO
				$response->status(Net_HTTP::STATUS_FILE_NOT_FOUND);
				$response->content = "Theme $mapped_theme not found";
				return;
			}
			$application->logger->debug('Executing theme={theme} mapped_theme={mapped_theme} args={args}', compact('theme', 'mapped_theme', 'args'));
		}
		$content = $application->theme($mapped_theme, $args, $theme_options); //TODO
		$response->content = $content;

		$json_html = $this->option('json_html', false);
		if ($json_html && $response->is_json() || $this->optionBool('json')) {
			$response->json()->data($response->html()->to_json() + [
				'status' => true,
			]);
		}
	}
}
