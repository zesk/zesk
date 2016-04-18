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
class Module_Forgot extends Module implements Module_Interface_Routes {
	
	/**
	 * 
	 * @var array
	 */
	protected $classes = array(
		"Forgot"
	);
	
	/**
	 * Implements Module::routes
	 *
	 * @param Router $router
	 */
	public function hook_routes(Router $router) {
		$router->add_route("forgot(/{option action}(/{hash}))", array(
			"controller" => "forgot",
			"arguments" => array(
				2
			),
			"id" => "forgot"
		));
	}
}