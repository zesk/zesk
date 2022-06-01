<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
abstract class Server_Packager {
	/**
	 * @var Server_Platform
	 */
	protected $platform = null;

	public function __construct(Server_Platform $platform) {
		$this->platform = $platform;
	}

	/**
	 * Install one or more packages. If it fails-half-way ... try to undo.
	 *
	 * @param mixed $packages String or array of packages to install.
	 */
	final public function install($packages) {
		$packages = to_list($packages);
		$remove = [];
		$result = [];
		foreach ($packages as $k => $package) {
			try {
				if ($this->package_install($package) === true) {
					array_unshift($remove, $package);
				}
			} catch (Server_Exception $e) {
				$this->keysRemove($remove, false);

				throw $e;
			}
		}
		return true;
	}

	public function configure() {
		return true;
	}

	final public function confirm($message) {
		return $this->platform->confirm($message);
	}

	/**
	 * Uninstall one or more packages. If any fail, an associative array returns with the package name and an error value.
	 * If all succeed, returns true
	 *
	 * @param mixed $packages String or array of strings to install.
	 * @return mixed true on success, or array of packagename => error
	 */
	final public function keysRemove($packages, $stop = true) {
		$packages = to_list($packages);
		$result = [];
		foreach ($packages as $k => $package) {
			try {
				$this->package_keysRemove($package);
			} catch (Server_Exception $e) {
				if ($stop) {
					throw $e;
				}
				$result[$k] = $e;
			}
		}
		return count($result) ? $result : true;
	}

	final public function root_exec($command) {
		$args = func_get_args();
		array_shift($args);
		return $this->platform->root_exec_array($command, $args);
	}

	final public function exec($command) {
		$args = func_get_args();
		array_shift($args);
		return $this->platform->exec_array($command, $args);
	}

	final public function exec_one($command) {
		$args = func_get_args();
		array_shift($args);
		return $this->platform->exec_one_array($command, $args);
	}

	/**
	 * Return an array of all packages installed
	 * @return array
	 */
	abstract public function packages();

	/**
	 * @return array
	 */
	abstract public function packages_update();

	/**
	 * @return array
	 */
	abstract public function package_exists($package);

	/**
	 * Install a single package
	 * @param string $package
	 * @return string error message, or true if succeeded
	 */
	abstract protected function package_install($package);

	/**
	 * Is a single package installed
	 * @param string $package
	 * @return boolean
	 */
	abstract protected function package_installed($package);

	/**
	 * Remove a single package
	 * @param string $package A package to remove
	 * @return boolean true if removed, false if not
	 */
	abstract protected function package_keysRemove($package);
}
