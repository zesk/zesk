<?php

class Server_Packager_MacPorts extends Server_Packager {
	protected function package_install($package) {
		if ($this->confirm("install $package")) {
			return $this->exec('port install {0}', $package);
		}
		return false;
	}

	protected function package_installed($package) {
		try {
			$this->exec('port info {0}', $package);
			return true;
		} catch (Server_Exception_Command $e) {
			return false;
		}
	}

	protected function package_remove($package) {
		if ($this->confirm("remove $package")) {
			return $this->exec('port remove {0}', $package);
		}
		return false;
	}

	public function package_exists($package) {
		list($port) = pair($this->root_exec_one("port list {0}", $package), " ", null, null);
		return strcasecmp($port, $package) === 0;
	}

	public function packages_update() {
		return $this->root_exec('port -d sync');
	}

	public function packages() {
		return arr::field($this->exec("port list installed"),0);
	}
}
