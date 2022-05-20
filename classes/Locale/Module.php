<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage locale
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk\Locale;

/**
 *
 */
use zesk\Router;
use zesk\Request;
use zesk\Template;
use zesk\Response;
use zesk\Application;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module implements \zesk\Interface_Module_Head, \zesk\Interface_Module_Routes {
	/**
	 * Output our locale translation files for JavaScript to use
	 *
	 * @param \Request $request
	 * @param \zesk\Response $response
	 */
	public function hook_head(Request $request, Response $response, Template $template): void {
		$response->javascript('/share/zesk/js/locale.js', [
			'weight' => -20,
			'share' => true,
		]);
		$response->javascript('/locale/js?ll=' . $this->application->locale->id(), [
			'weight' => -10,
			'is_route' => true,
			'route_expire' => 3600, /* once an hour */
		]);
	}

	/**
	 * Register all hooks
	 */
	public function initialize(): void {
		parent::initialize();
		$this->application->configuration->deprecated('zesk\\Controller_Locale', Controller::class);
		$this->application->configuration->deprecated('zesk\\Locale_Validate', Validate::class);
		// 		$this->application->hooks->add(Application::class . "::router_loaded", array(
		// 			$this,
		// 			"router_loaded"
		// 		));
	}

	/**
	 *
	 * @param \zesk\Application $app
	 * @param Router $router
	 */
	public function hook_routes(Router $router): void {
		$router->add_route('/locale/{option action}', [
			'controller' => Controller::class,
			'arguments' => [
				1,
			],
		]);
	}
}
