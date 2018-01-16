<?php
/**
 * Handle integration and hooks into Zesk
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/forgot/classes/Module/Forgot.php $
 * @package zesk
 * @subpackage forgot
 * @author kent
 * @copyright &copy; 2014 Market Acumen, Inc.
 */
namespace zesk;

/**
 * Forgotten password support
 *
 * @author kent
 */
class Module_Forgot extends Module implements Interface_Module_Routes {

	/**
	 *
	 * @var array
	 */
	protected $model_classes = array(
		"zesk\\Forgot"
	);

	/**
	 *
	 * {@inheritDoc}
	 * @see Module::initialize()
	 */
	public function initialize() {
		parent::initialize();
		$this->zesk->configuration->path("zesk\\Forgot")->theme_path_prefix = "object";
	}

	/**
	 * Implements Module::routes
	 *
	 * @param Router $router
	 */
	public function hook_routes(Router $router) {
		$router->add_route("forgot(/{option action}(/{hash}))", array(
			"controller" => "zesk\\Controller_Forgot",
			"classes" => array(
				"zesk\\Forgot"
			),
			"arguments" => array(
				2
			),
			"login" => false,
			"id" => "forgot"
		));
	}

	/**
	 *
	 * @param Application $application
	 */
	public function cron_cluster_minute() {
		$expire_seconds = -abs(to_integer($this->option("expire_seconds"), 3600));
		$older = Timestamp::now()->add_unit($expire_seconds, Timestamp::UNIT_SECOND);
		$affected_rows = $this->application->orm_registry(Forgot::class)->delete_older($older);
		if ($affected_rows > 0) {
			$this->application->logger->notice("{method} deleted {affected_rows} forgotten rows", compact("affected_rows") + array(
				"method" => __METHOD__
			));
		}
	}
}
