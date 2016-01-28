<?php
class Route_Theme extends Route {

	/**
	 * Whether the theme path is set by variables
	 * @var unknown
	 */
	protected $dynamic_theme = false;

	protected function initialize() {
		parent::initialize();
		$theme = $this->option("theme");
		if (map_clean($theme) !== $theme) {
			$this->dynamic_theme = true;
			return;
		}
		if ($this->router->application->theme_exists($theme)) { //TODO
			return;
		}
		$theme = str::unsuffix($this->option("template"), ".tpl");
		if ($this->router->application->theme_exists($theme)) { //TODO
			$this->set_option("theme", $theme);
			return;
		}
		throw new Exception_File_NotFound("No theme $theme found in " . implode(", ", zesk::theme_path())); //TODO
	}

	function _execute(Application $app) {
		$parameters = $app->variables() + array(
			'route' => $this
		);
		$parameters += $this->options + $this->named;
		$args = map($this->option_array("theme arguments", array()), $parameters) + $parameters;
		$mapped_theme = $theme = $this->option('theme');
		if ($this->dynamic_theme) {
			$mapped_theme = map($theme, $parameters);
			if (!$this->router->application->theme_exists($mapped_theme)) { //TODO
				$app->response->status(Net_HTTP::Status_File_Not_Found);
				$app->response->content = "Theme $mapped_theme not found";
				return;
			}
		}
		log::debug("Executing theme={theme} mapped_theme={mapped_theme} args={args}", compact("theme", "mapped_theme", "args"));
		$content = $this->router->application->theme($mapped_theme, $args); //TODO
		if ($app->response->json()) {
			return;
		}
		if ($this->option_bool('json')) {
			$app->response->json($app->response->to_json() + array(
				"content" => $content,
				"status" => true
			));
		} else {
			$app->response->content = $content;
		}
	}
}
