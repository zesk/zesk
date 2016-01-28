<?php

class Server_Feature_MacPorts extends Server_Feature {
	protected $platforms = array("Darwin");

	protected $settings = array("MACPORTS_INSTALL_PATH" => "path");

	protected $defaults = array("MACPORTS_INSTALL_PATH" => "/opt/mports");

	protected $commands = array("svn", "gcc", "make");

	protected $dependencies = array("svn");

	protected $packages = array();

	protected $command_paths = array("/opt/local/bin/");

	function install() {
		$path = $this->path("MACPORTS_INSTALL_PATH");

		$this->create_path($path);
		$this->change_directory($path);
		$this->root_exec("svn checkout http://svn.macports.org/repository/macports/trunk");
		$this->change_directory(path($path, "trunk/base"));
		$this->root_exec("./configure --enable-readline");
		$this->root_exec("make install");
		$this->root_exec("make distclean");

		if (!$this->has_shell_command("port")) {
			throw new Server_Exception_Install("port command does not exist after successful installation ... path issue?");
		}

		return true;
	}

	function configure() {

	}
}