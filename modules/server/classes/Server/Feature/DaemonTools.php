<?php
namespace zesk;

class Server_Feature_Daemontools extends Server_Feature {
    protected $commands = array();

    protected $dependencies = array();

    protected $settings = array();

    public function configure() {
        $tool = path($this->configure_root, 'daemontools-restart.sh');
        $this->owner($this->install_tool($tool), $this->platform->root_user(), 0750);
        if ($this->platform->process_is_running('svscan')) {
            $this->verbose_log("Daemontools appears to be installed and running correctly ...");
            return true;
        }

        if (!$this->confirm("Install daemontools")) {
            return false;
        }

        $qmail_ver = 'netqmail-1.05';
        $daemontools_ver = 'daemontools-0.76';

        $this->begin("daemontools configure");

        try {
            $path = $this->remote_package("http://cr.yp.to/daemontools/$daemontools_ver.tar.gz");
            $this->require_directory("/package", "root", 0700);
            $this->exec("tar -C /package zxpf $path");
            $package_dir = "/package/admin/$daemontools_ver/src";
            if (!is_dir($package_dir)) {
                throw new Exception_Directory_NotFound("Unpacking $path in /package should have created $package_dir but didn't");
            }
            $patch = path($this->configure_root, "$daemontools_ver.errno.patch");
            $this->exec("patch -d $package_dir < $patch");
            $this->exec("/package/admin/$daemontools_ver/package/install");
            $this->verbose_log("Sleeping to allow daemontools to run");
            $tries = 0;
            while (!$this->platform->process_is_running('svscan')) {
                if ($tries >= 5) {
                    throw new Server_Exception("svscan is not running");
                }
                sleep(2);
                $this->verbose_log("Trying to run daemontools");
                $this->root_exec("csh -cf '/command/svscanboot &");
                ++$tries;
                sleep(2);
            }
        } catch (Exception $e) {
            $this->end_fail();

            throw $e;
        }
    }
}
