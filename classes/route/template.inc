<?php

/**
 * @author kent
 *
 */
class Route_Template extends Route {
	protected function initialize(Application $application) {
		parent::initialize($application);
		$template = $this->option("template");
		if (!Template::would_exist($template)) {
			throw new Exception_File_NotFound("No template $template found in " . implode(", ", zesk::theme_path()));
		}
	}
	function _execute(Application $app) {
		$parameters = $app->variables() + array(
			'route' => $this
		);
		$parameters += $this->options + $this->named;
		$content = Template::instance($this->option("template"), $parameters);
		if (($theme = $this->option('theme')) !== null) {
			$args = $this->option_array("theme arguments", array()) + array(
				"content" => $content
			) + $parameters;
			$content = $app->theme($theme, $args);
		}
		$app->response->content = $content;
	}
}
