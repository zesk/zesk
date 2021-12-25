<?php declare(strict_types=1);

/**
 *
 */
namespace zesk\AWS;

use zesk\Server;
use zesk\Exception_File_Permission;
use zesk\AWS\EC2\Awareness;

/**
 *
 * @author kent
 *
 */
class Module extends \zesk\Module {
	/**
	 * Path to magic file which gives the hypervisor as belonging to ec2
	 *
	 * @var string
	 */
	public const PATH_SYSTEM_HYPERVISOR_UUID_PATH = '/sys/hypervisor/uuid';

	/**
	 *
	 * @var boolean
	 */
	private $is_ec2 = null;

	/**
	 *
	 * @var Awareness
	 */
	private $awareness;

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \zesk\Module::initialize()
	 */
	public function initialize(): void {
		parent::initialize();
		if (!$this->is_ec2()) {
			return;
		}
		if (!$this->option_bool("disable_server_integration")) {
			$this->application->hooks->add(Server::class . "::initialize_names", [
				$this,
				"server_initialize_names",
			]);
		}
		if (!$this->option_bool("disable_uname_integration")) {
			$self = $this;
			$this->application->hooks->add("uname", fn () => $self->awareness()->get(Awareness::setting_mac));
		}
	}

	/**
	 *
	 * @param array $options
	 * @return Awareness
	 */
	public function awareness(array $options = []) {
		if ($this->awareness) {
			return $this->awareness;
		}
		$this->awareness = new Awareness($this->application, $options);
		return $this->awareness;
	}

	/**
	 * Hook to initialize a server's name
	 *
	 * @param Server $server
	 */
	public function server_initialize_names(Server $server): void {
		$awareness = $this->awareness();
		if ($this->option_bool("disable_uname_integration")) {
			$server->name = php_uname('n');
		}
		$server->ip4_internal = $awareness->get(Awareness::setting_local_ipv4);
		$server->ip4_external = $awareness->get(Awareness::setting_public_ipv4);
		$server->name_internal = $awareness->get(Awareness::setting_local_hostname);
		$server->name_external = $awareness->get(Awareness::setting_public_hostname);
	}

	/**
	 * Adapted from
	 * http://serverfault.com/questions/462903/how-to-know-if-a-machine-is-an-ec2-instance
	 *
	 * @return boolean
	 */
	public function is_ec2() {
		if ($this->is_ec2 !== null) {
			return $this->is_ec2;
		}
		$f = self::PATH_SYSTEM_HYPERVISOR_UUID_PATH;
		if (!file_exists($f)) {
			return $this->is_ec2 = false;
		}
		$fp = fopen($f, 'rb');
		if (!$fp) {
			throw new Exception_File_Permission($f, "Unable to open for reading");
		}
		$ec2 = fread($fp, 3);
		fclose($fp);
		return $this->is_ec2 = $ec2 === "ec2";
	}
}
