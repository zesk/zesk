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
	 * @deprecated 2016-12
	 * @see zesk\Application::session_class
	 * @param string $set
	 * @return mixed|array
	 */
	public static function implementation($set = null) {
		global $zesk;
		/* @var $zesk zesk\Kernel */
		if ($set !== null) {
			$set = strtolower($set);
			$set = avalue(self::$aliases, $set, $set);
			$zesk->configuration->pave("session")->implementation = $set;
			return $set;
		}
		$get = $zesk->configuration->pave("session")->get("implementation", "");
		return avalue(self::$aliases, $get, $get);
	}
	private static function session_class(Application $application) {
		$default_class = self::implementation();
		if ($default_class) {
			zesk()->deprecated("Session::implementation configuration value is deprecated, use zesk\Application::session_class instead (set to \"$default_class\")");
			$default_class = __NAMESPACE__ . "\\Session_" . $default_class;
		} else {
			$default_class = __NAMESPACE__ . "\\Session_PHP";
		}
		$class = $application->option("session_class", $default_class);
		return $class;
	}
	/**
	 * Retrieve the current session, creating it if specified and if nececssary.
	 *
	 * @see app()->session();
	 * @deprecated 2016-12 
	 * @param boolean $create
	 *        	Create a session if one does not exist
	 * @return zesk\Interface_Session
	 */
	public static function instance() {
		zesk()->deprecated();
		return self::factory(app());
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
		return zesk()->objects->factory($class, null, null, $application)->initialize_session($application->request());
	}
}
