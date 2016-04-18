<?php
class Module_AWS extends Module {
	/**
	 * Path to magic file which gives the hypervisor as belonging to ec2
	 * 
	 * @var string
	 */
	const path_system_hypervisor_uuid = '/sys/hypervisor/uuid';
	
	/**
	 * 
	 * @var boolean
	 */
	private $is_ec2 = null;
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Module::initialize()
	 */
	public function initialize() {
		parent::initialize();
		if (!$this->is_ec2()) {
			return;
		}
		if (!$this->option_bool("disable_server_integration")) {
			zesk::add_hook("Server::initialize_names", array(
				$this,
				"server_initialize_names"
			));
		}
		if (!$this->option_bool("disable_uname_integration")) {
			zesk::add_hook("uname", function () {
				return zesk::factory("AWS_EC2_Awareness")->get(AWS_EC2_Awareness::setting_mac);
			});
		}
	}
	
	/**
	 * Hook to initialize a server's name
	 * 
	 * @param Server $server
	 */
	public function server_initialize_names(Server $server) {
		$awareness = new AWS_EC2_Awareness();
		if ($this->option_bool("disable_uname_integration")) {
			$server->name = php_uname('n');
		}
		$server->ip4_internal = $awareness->get(AWS_EC2_Awareness::setting_local_ipv4);
		$server->ip4_external = $awareness->get(AWS_EC2_Awareness::setting_public_ipv4);
		$server->name_internal = $awareness->get(AWS_EC2_Awareness::setting_local_hostname);
		$server->name_external = $awareness->get(AWS_EC2_Awareness::setting_public_hostname);
	}
	
	/**
	 * Adapted from http://serverfault.com/questions/462903/how-to-know-if-a-machine-is-an-ec2-instance
	 * 
	 * @return boolean
	 */
	public function is_ec2() {
		if ($this->is_ec2 !== null) {
			return $this->is_ec2;
		}
		$f = self::path_system_hypervisor_uuid;
		if (!file_exists($f)) {
			return $this->is_ec2 = false;
		}
		$fp = fopen($f, 'r');
		if (!$fp) {
			throw new Exception_File_Permission($f, "Unable to open for reading");
		}
		$ec2 = fread($fp, 3);
		fclose($fp);
		return $this->is_ec2 = $ec2 === "ec2";
	}
}
