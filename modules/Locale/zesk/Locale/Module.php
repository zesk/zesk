<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage locale
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Locale;

/**
 *
 */

use zesk\Exception_File_NotFound;
use zesk\Exception_File_Permission;
use zesk\Exception_Semantics;
use zesk\Exception_Unimplemented;
use zesk\Interface_Module_Head;
use zesk\Interface_Module_Routes;
use zesk\Locale;
use zesk\PHP;
use zesk\Request;
use zesk\Response;
use zesk\Router;
use zesk\Template;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module implements Interface_Module_Head, Interface_Module_Routes {
	public function initialize(): void {
		parent::initialize();
		$this->application->hooks->add(Locale::class . '::shutdown', $this->shutdownLocale(...));
	}

	public function shutdownLocale(Locale $locale, array $phrases, string $phrasesContext): void {
		if (count($phrases) === 0) {
			return;
		}
		$autoPath = $this->option('autoPath');
		if (!$autoPath) {
			return;
		}
		$path = $this->application->paths->expand($autoPath);
		if (!is_dir($path)) {
			$this->application->logger->warning('{class}::autoPath {path} is not a directory', [
				'path' => $path, 'class' => get_class($this),
			]);
			return;
		}
		$writer = new Writer($this->application, path($path, $locale->id() . '-auto.php'), $locale->id());

		try {
			$writer->append($phrases, $phrasesContext);
		} catch (Exception_Unimplemented|Exception_File_NotFound|Exception_File_Permission $e) {
			PHP::log($e);
		}
	}

	/**
	 * Output our locale translation files for JavaScript to use
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function hook_head(Request $request, Response $response, Template $template): void {
		try {
			$response->html()->javascript('/share/Locale/js/locale.js', [
				'weight' => -20, 'share' => true,
			]);
			$response->html()->javascript('/locale/js?ll=' . $this->application->locale->id(), [
				'weight' => -10, 'is_route' => true, 'route_expire' => 3600, /* once an hour */
			]);
		} catch (Exception_Semantics $e) {
			/* Should never happen - only if options contain 'after' or 'before' */
			PHP::log($e);
		}
	}

	/**
	 *
	 * @param Router $router
	 */
	public function hook_routes(Router $router): void {
		$router->addRoute('locale(/{option action})', [
			'controller' => Controller::class,
		]);
	}
}