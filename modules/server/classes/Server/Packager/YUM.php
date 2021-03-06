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
class Server_Packager_YUM extends Server_Packager {
	protected function package_install($package) {
		return $this->exec('yum -y install {0}', $package);
	}

	protected function package_installed($package) {
		try {
			$this->exec('yum list installed {0} | grep -q {0}\'.*installed\'', $package);
			return true;
		} catch (Server_Exception $e) {
			return false;
		}
	}

	protected function package_remove($package) {
		return $this->exec('yum remove {0}', $package);
	}

	public function packages_update() {
		return $this->exec('yum update');
	}

	public function packages() {
		throw new Exception_Unimplemented("No longer have access to yum system");
	}

	public function package_exists($package) {
		throw new Exception_Unimplemented("package_exists");
		return false;
	}
}
