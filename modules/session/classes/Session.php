<?php

/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Session {
	/**
	 * Provide simple aliases for
	 *
	 * @var array
	 */
	private static $aliases = array(
		"db" => "database"
	);
	
	/**
	 *
	 * @param Kernel $zesk
	 */
	public static function hooks(Kernel $zesk) {
		$zesk->configuration->deprecated("Session", __CLASS__);
	}
	/**
	 *
	 * @return string
	 */
	private static function _implementation(Configuration $configuration) {
		$get = $configuration->path(__CLASS__)->get("implementation", "");
		return avalue(self::$aliases, $get, $get);
	}
	
	/**
	 *
	 * @param Application $application
	 * @return mixed|string|array
	 */
	private static function session_class(Application $application) {
		$default_class = self::_implementation($application->configuration);
		if ($default_class) {
			$application->zesk->deprecated("Session::implementation configuration value is deprecated, use zesk\Application::session_class instead (set to \"$default_class\")");
			$default_class = __NAMESPACE__ . "\\" . "Session_" . $default_class;
		} else {
			$default_class = __NAMESPACE__ . "\\" . "Session_PHP";
		}
		$class = $application->option("session_class", $default_class);
		return $class;
	}
	
	/**
	 * Retrieve the session
	 *
	 * @param Application $application
	 * @throws Exception_Configuration
	 */
	public static function factory(Application $application) {
		$class = self::session_class($application);
		if (!$class) {
			throw new Exception_Configuration("zesk\Application::session_implementation", "Needs a class name value");
		}
		return $application->objects->factory($class, $application)->initialize_session($application->request());
	}
}
