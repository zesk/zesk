<?php
namespace zesk;

use zesk\Test\Controller;

class Test_Module extends Module implements Interface_Module_Routes {
	/**
	 *
	 * @var string
	 */
	private $phpunit = null;

	/**
	 */
	public function initialize() {
		parent::initialize();
		$this->phpunit = $this->application->path("vendor/bin/phpunit");
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Interface_Module_Routes::hook_routes()
	 */
	public function hook_routes(Router $router) {
		$router->add_route("test", array(
			"controller" => Controller::class,
			"action" => array(
				1
			),
			"default action" => "index"
		));
	}

	/**
	 *
	 * @return boolean
	 */
	public function has_phpunit() {
		return file_exists($this->phpunit) && is_executable($this->phpunit);
	}
}
