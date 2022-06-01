<?php declare(strict_types=1);
/**
 * @package zesk-modules
 * @subpackage test
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\Test;

use zesk\Router;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module implements \zesk\Interface_Module_Routes {
	/**
	 *
	 * @var string
	 */
	private $phpunit = null;

	/**
	 */
	public function initialize(): void {
		parent::initialize();
		$this->phpunit = $this->application->path('vendor/bin/phpunit');
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Interface_Module_Routes::hook_routes()
	 */
	public function hook_routes(Router $router): void {
		$router->add_route('test(/{option action}(/{arg}))', [
			'controller' => Controller::class,
			'arguments' => [
				2,
			],
			'default action' => 'index',
		]);
	}

	/**
	 *
	 * @return boolean
	 */
	public function has_phpunit() {
		return file_exists($this->phpunit) && is_executable($this->phpunit);
	}
}
