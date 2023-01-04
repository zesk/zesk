<?php declare(strict_types=1);
namespace zesk;

class Server_Feature_HTTPD extends Server_Feature {
	public $name = 'httpd';

	protected $settings = [
		'packages' => 'package list',
		'conf_home' => 'directory',
		'bin' => 'executable',
		'control' => 'executable',
		'enable_module' => 'executable',
		'user' => 'user',
		'group' => 'group',
	];

	protected $features = [
		'users',
	];

	protected $defaults = [];

	private $exec_control = null;

	public function initialize(): void {
		$this->packages = $this->config->package_dependency_list('packages');
	}

	public function install(): void {
		parent::install();
		/*
		 * Service install
		 */
		// if ($this->shell_command_exists('update-rc.d')) {
		// $this->root_exec('update-rc.d -f apache2 remove > /dev/null');
		// }
		// $this->install_service("httpd", path($this->configure_root, 'service'));
		$modules = $this->config->feature_list('modules');
		$command = $this->config->executable('enable_modules');
		foreach ($modules as $module) {
			$this->root_exec("$command $module");
		}
	}

	public function beforeConfigure(): void {
		/* @var $users Server_Feature_Users */
		$users = $this->config->feature('users');
		$users->require_user($this->config->variable('httpd::user'));
		$users->require_group($this->config->variable('httpd::group'));
		parent::beforeConfigure();
	}

	public function configure(): void {
		$config = $this->config;
		$httpd_conf_path = $config->option('HTTPD_CONF_HOME');
		$owner = $config->user('HTTPD_USER') . ':' . $config->group('HTTPD_GROUP');
		$this->require_directory(path($config->option('LOG_PATH'), 'httpd'), $owner, 0o755);
		$this->require_directory($httpd_conf_path, $owner, 0o755);

		/*
		 * $host_path = $this->configure_path('httpd'); if (!is_dir($host_path)) { throw new
		 * Server_Exception("httpd host path $host_path not found"); } $this->verboseLog("httpd
		 * host path is $host_path");
		 */

		$this->begin('Configuring httpd service ...');

		$feature_dir = $config->feature_directory('httpd');

		$this->verboseLog('Checking httpd configuration ...');
		$this->_check_configuration($config, $feature_dir);

		$this->verboseLog('Updating HTTPD Configuration');

		$changed = $this->update($feature_dir, $httpd_conf_path);

		$this->verboseLog('Checking installed configuration ...');

		$this->_check_configuration($config, $httpd_conf_path);

		if ($changed && $this->confirm('Restart httpd')) {
			$this->restart_service('httpd');
		}
	}

	private function apache_control($arguments): void {
		$args = func_get_args();
		array_shift($args);
		$command = $this->config->executable('HTTPD_CONTROL');
		$command .= " $arguments";
		$this->root_exec_array($command, $args);
	}

	private function _check_configuration(Server_Configuration $config, $server_root, $httpd_conf_file = null): void {
		if ($httpd_conf_file === null) {
			$httpd_conf_file = $config->get('HTTPD_CONF_RELATIVE_PATH');
		}
		$httpd_conf_file = path($server_root, $httpd_conf_file);

		try {
			$this->apache_control('-d {0} -f {1} -t', $server_root, $httpd_conf_file);
		} catch (Server_Exception $e) {
			$this->verboseLog("Errors in httpd.conf, fix $server_root/$httpd_conf_file and dependencies before restarting httpd");

			throw $e;
		}
	}

	private function _auto_configure(Server_Configuration $config): void {
	}
}

/*
Server version: Apache/2.2.3
Server built:   Nov 14 2009 10:57:36
Server's Module Magic Number: 20051115:3
Server loaded:  APR 1.2.7, APR-Util 1.2.7
Compiled using: APR 1.2.7, APR-Util 1.2.7
Architecture:   64-bit
Server MPM:     Worker
  threaded:     yes (fixed thread count)
	forked:     yes (variable process count)
Server compiled with....
 -D APACHE_MPM_DIR="server/mpm/worker"
 -D APR_HAS_SENDFILE
 -D APR_HAS_MMAP
 -D APR_HAVE_IPV6 (IPv4-mapped addresses enabled)
 -D APR_USE_SYSVSEM_SERIALIZE
 -D APR_USE_PTHREAD_SERIALIZE
 -D SINGLE_LISTEN_UNSERIALIZED_ACCEPT
 -D APR_HAS_OTHER_CHILD
 -D AP_HAVE_RELIABLE_PIPED_LOGS
 -D DYNAMIC_MODULE_LIMIT=128
 -D HTTPD_ROOT=""
 -D SUEXEC_BIN="/usr/lib/apache2/suexec"
 -D DEFAULT_PIDLOG="/var/run/apache2.pid"
 -D DEFAULT_SCOREBOARD="logs/apache_runtime_status"
 -D DEFAULT_ERRORLOG="logs/error_log"
 -D AP_TYPES_CONFIG_FILE="/etc/apache2/mime.types"
 -D SERVER_CONFIG_FILE="/etc/apache2/apache2.conf"
 */
