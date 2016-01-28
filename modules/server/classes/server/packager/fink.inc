<?php

class Server_Packager_Fink extends Server_Packager {
	protected function package_install($package) {
		return $this->exec('fink install {0}', $package);
	}

	/**
	 * @see Server_Packager::package_installed()
	 */
	protected function package_installed($package) {
		try {
			$result = $this->exec('fink list -i | grep {0} | awk \'{ print $2 }\'', $package);
			return trim($result[0]) !== "";
		} catch (Server_Exception $e) {
			return false;
		}
	}

	protected function package_remove($package) {
		$full_package = self::installed_name($package);
		return $this->exec('fink remove {0}', $full_package);
	}

	public function packages_update() {
		return $this->exec('fink update');
	}
	public function packages() {
		return arr::trim($this->exec('fink list -i | awk \'{ print $2 }\''));
	}
	public function package_exists($package) {
		throw new Exception_Unimplemented("package_exists");
		return false;
	}

}
