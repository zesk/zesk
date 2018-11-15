<?php
/**
 * @package zesk
 * @subpackage locale
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk\Locale;

/**
 *
 */
use zesk\Router;
use zesk\Request;
use zesk\Template;
use zesk\Response;

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
    public function hook_head(Request $request, Response $response, Template $template) {
        $response->javascript("/share/zesk/js/locale.js", array(
            "weight" => -20,
            "share" => true,
        ));
        $response->javascript("/locale/js?ll=" . $this->application->locale->id(), array(
            "weight" => -10,
            "is_route" => true,
            "route_expire" => 3600, /* once an hour */
        ));
    }

    /**
     * Register all hooks
     */
    public function initialize() {
        parent::initialize();
        $this->application->configuration->deprecated("zesk\\Controller_Locale", Controller::class);
        $this->application->configuration->deprecated("zesk\\Locale_Validate", Validate::class);
        $this->application->hooks->add("zesk\Application::router_loaded", array(
            $this,
            "router_loaded",
        ));
    }

    /**
     *
     * @param \zesk\Application $app
     * @param Router $router
     */
    public function hook_routes(Router $router) {
        $router->add_route("/locale/{option action}", array(
            "controller" => Controller::class,
            "arguments" => array(
                1,
            ),
        ));
    }
}
