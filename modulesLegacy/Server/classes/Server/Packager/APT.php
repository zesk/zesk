<?php declare(strict_types=1);
/**
 *
 */
namespace Server\classes\Server\Packager;

use Server\classes\Server\Server_Exception;
use Server\classes\Server\Server_Packager;
use zesk\Exception_Unimplemented;

/**
 *
 * @author kent
 *
 */
class Server_Packager_APT extends Server_Packager {
	protected function package_install($package) {
		if ($this->confirm("install $package")) {
			return $this->exec('apt-get -y install {0}', $package);
		}

		throw new Server_Exception("Install $package{ declined");
	}

	protected function package_installed($package) {
		return $this->exec('apt-show-versions --package={0} | grep -q \'not installed\'', $package);
	}

	protected function package_keysRemove($package) {
		return $this->exec('apt-get remove {0}', $package);
	}

	public function package_exists($package) {
		throw new Exception_Unimplemented('package_exists');
		return false;
	}

	public function packages_update() {
		return $this->exec('apt-get update');
	}

	public function packages() {
		return $this->exec('apt-show-versions -b | cut -d / -f 1');
	}

	public function configure(): void {
		$this->install('apt-show-versions');
		if ($this->root_inherit_copy('apt/sources.list', '/etc/apt/sources.list')) {
			$this->packager->update();
		}
	}
}
