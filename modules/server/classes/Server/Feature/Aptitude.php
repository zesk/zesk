<?php
namespace zesk;

class Server_Feature_Aptitute extends Server_Feature {
    public function configure() {
        if (!$this->platform->packager instanceof Server_Packager_APT) {
            $this->application->logger->warning("Server_Feature_Aptitute being configured, but packager is {class}", array(
                "class" => get_class($this->platform->packager),
            ));
        }
        $this->configuration_files('apt', array(
            'sources.list',
            'preferences.d/',
            'sources.list.d/',
            'trusted.gpg.d/',
            'trustdb.gpg',
            'trusted.gpg',
        ), '/etc/apt/', array(
            "user" => "root",
            "mode" => 0755,
        ));
    }
}
