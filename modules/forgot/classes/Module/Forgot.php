<?php declare(strict_types=1);
/**
 * Handle integration and hooks into Zesk
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/forgot/classes/Module/Forgot.php $
 * @package zesk
 * @subpackage forgot
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk;

/**
 * Forgotten password support
 *
 * @see Forgot
 * @see Controller_Forgot
 * @author kent
 */
class Module_Forgot extends Module implements Interface_Module_Routes {
	/**
	 *
	 * @var array
	 */
	protected array $model_classes = [
		"zesk\\Forgot",
	];

	/**
	 *
	 * {@inheritDoc}
	 * @see Module::initialize()
	 */
	public function initialize(): void {
		parent::initialize();
		$this->application->configuration->path(Forgot::class)->theme_path_prefix = "object";
	}

	/**
	 * Implements Module::routes
	 *
	 * @param Router $router
	 */
	public function hook_routes(Router $router): void {
		$router->add_route("forgot(/{option action}(/{hash}))", $this->option_array("route_options") + [
			"controller" => Controller_Forgot::class,
			"classes" => [
				Forgot::class,
			],
			"arguments" => [
				2,
			],
			"login" => false,
			"id" => "forgot",
		]);
	}

	/**
	 * Default value for database_expire_seconds (1 day)
	 *
	 * @var integer
	 */
	public const DEFAULT_DATABASE_EXPIRE_SECONDS = 86400;

	/**
	 * Default value for request_expire_seconds (# seconds after which requests are considered invalid by the browser) (1 hour)
	 *
	 *
	 * @var integer
	 */
	public const DEFAULT_REQUEST_EXPIRE_SECONDS = 3600;

	/**
	 * Represents the number of seconds after which database entries are considered invalid.
	 *
	 * @return integer
	 */
	public function request_expire_seconds() {
		return $this->option("request_expire_seconds", self::DEFAULT_REQUEST_EXPIRE_SECONDS);
	}

	/**
	 * Represents the number of seconds after which database entries are deleted. Must be greater than "request_expire_seconds" above, and
	 * is enforced.
	 *
	 * @return integer
	 */
	public function database_expire_seconds() {
		return max($this->optionInt("expire_seconds", self::DEFAULT_DATABASE_EXPIRE_SECONDS), $this->request_expire_seconds());
	}

	/**
	 *
	 * @param Application $application
	 */
	public function hook_cron_cluster_minute(): void {
		$expire_seconds = -abs(to_integer($this->option("expire_seconds"), 3600));
		$older = Timestamp::now()->add_unit($expire_seconds, Timestamp::UNIT_SECOND);
		$affected_rows = $this->application->orm_registry(Forgot::class)->delete_older($older);
		if ($affected_rows > 0) {
			$this->application->logger->notice("{method} deleted {affected_rows} forgotten rows", compact("affected_rows") + [
				"method" => __METHOD__,
			]);
		}
	}
}
