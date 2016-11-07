<?php
/**
 * Handle integration and hooks into Zesk
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/forgot/classes/module/forgot.inc $
 * @package zesk
 * @subpackage forgot
 * @author kent
 * @copyright &copy; 2014 Market Acumen, Inc.
 */

/**
 * Forgotten password support
 *
 * @author kent
 */
class Module_Forgot extends Module implements zesk\Interface_Module_Routes {
	
	/**
	 *
	 * @var array
	 */
	protected $classes = array(
		"Forgot"
	);
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Module::initialize()
	 */
	public function initialize() {
		parent::initialize();
		$this->zesk->configuration->pave("Forgot")->theme_path_prefix = "object";
	}
	
	/**
	 * Implements Module::routes
	 *
	 * @param Router $router        	
	 */
	public function hook_routes(zesk\Router $router) {
		$router->add_route("forgot(/{option action}(/{hash}))", array(
			"controller" => "forgot",
			"arguments" => array(
				2
			),
			"login" => false,
			"id" => "forgot"
		));
	}
}
