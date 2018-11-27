<?php
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
	protected function initialize() {
		parent::initialize();
		$theme = $this->option("theme");
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
	public function validate() {
		$application = $this->router->application;
		$parameters = $application->variables() + array(
			'route' => $this,
		);
		$parameters += $this->options + $this->named;
		$args = map($this->option_array("theme arguments", array()), $parameters) + $parameters;
		$theme_options = $this->option_array("theme options");
		$theme = $this->option("theme");
		if ($application->theme_exists($theme, $args, $theme_options)) {
			return $this;
		}

		throw new Exception_File_NotFound("No theme {theme} found in {theme_paths}", array(
			"theme" => $theme,
			"theme_paths" => $application->theme_path(),
		));
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see Route::_execute()
	 */
	public function _execute(Response $response) {
		$application = $this->router->application;
		$parameters = $application->variables() + array(
			'route' => $this,
		);
		$parameters += $this->options + $this->named;
		$args = map($this->option_array("theme arguments", array()), $parameters) + $parameters;
		$mapped_theme = $theme = $this->option('theme');
		$theme_options = $this->option_array("theme options");
		if ($this->dynamic_theme) {
			$mapped_theme = map($theme, $parameters);
			if (!$application->theme_exists($mapped_theme, $args, $theme_options)) { //TODO
				$response->status(Net_HTTP::STATUS_FILE_NOT_FOUND);
				$response->content = "Theme $mapped_theme not found";
				return;
			}
			$application->logger->debug("Executing theme={theme} mapped_theme={mapped_theme} args={args}", compact("theme", "mapped_theme", "args"));
		}
		$content = $application->theme($mapped_theme, $args, $theme_options); //TODO
		if ($response->is_json()) {
			return;
		}
		if ($this->option_bool('json')) {
			return $response->json()->data($response->to_json() + array(
				"content" => $content,
				"status" => true,
			));
		} else {
			$response->content = $content;
		}
	}
}
