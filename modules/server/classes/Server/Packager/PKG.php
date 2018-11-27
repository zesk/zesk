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
class Server_Packager_PKG extends Server_Packager {
    protected function package_install($package) {
        return $this->exec('pkg_add -r {0}', $package);
    }

    /**
     * @see Server_Packager::package_installed()
     */
    protected function package_installed($package) {
        try {
            $result = $this->exec('pkg_info | grep {0} | awk \'{ print $1 }\'', $package);
            return trim($result[0]) !== "";
        } catch (Server_Exception $e) {
            return false;
        }
    }

    protected function installed_name($package) {
        $result = $this->exec('pkg_info | grep {0} | awk \'{ print $1 }\'', $package);
        return trim($result[0]);
    }

    protected function package_remove($package) {
        $full_package = self::installed_name($package);
        return $this->exec('pkg_delete {0}', $full_package);
    }

    public function packages_update() {
        return $this->exec('freedbsd-update fetch');
    }

    public function packages() {
        return $this->exec('pkg_info | awk \'{ print $0 |\'');
    }

    public function package_exists($package) {
        throw new Exception_Unimplemented("package_exists");
        return false;
    }
}
