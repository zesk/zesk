<?php
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Server_Feature_FreeBSD extends Server_Feature {
    // "bison", "re2c", "autoconf262", "automake19", "libtool", "cvsup-without-gui"
    protected $packages = array();

    protected $platforms = array(
        "FreeBSD",
    );

    protected $settings = array(
        "RC_CONF_PATH" => "path list",
    );

    public function configure() {
        $source = $this->find_inherit_file("boot/loader.conf");
        if ($source) {
            $this->update($source, "/boot/loader.conf");
        }
        $paths = $this->get_path_list("RC_CONF_PATH");

        $sysctl_conf = "/etc/sysctl.conf";

        if ($this->update_catenate(basename($sysctl_conf), $paths, $sysctl_conf)) {
            $this->sysctl_apply();
        }
        $this->owner($sysctl_conf, "root", 0644);

        $rc_conf = "/etc/rc.conf";

        $this->update_catenate(basename($rc_conf), $paths, $rc_conf);
        $this->owner($rc_conf, "root", 0644);

        $rc_extras = array();
        foreach ($paths as $path) {
            $rc_files = Directory::ls($path, '/rc\.[-._a-zA-Z0-9]*\.conf/', true);
            if (count($rc_files)) {
                foreach ($rc_files as $rc_file) {
                    $dest = path('/etc/', basename($rc_file));
                    $this->update($rc_file, $dest, true);
                    $this->owner($dest, "root", 0644);
                }
            }
        }
    }

    private function sysctl_apply() {
        $this->root_exec("/sbin/sysctl `grep -v '^#.*' /etc/sysctl.conf");
    }
}
