<?php
/**
 * 
 */
use zesk\Database;
use zesk\Exception_Configuration;

/**
 * 
 * @author kent
 *
 */
class Server_Feature_PureFTPD extends Server_Feature {
	protected $settings = array(
		'authentications' => 'list',
		'start_options' => array(
			'type' => 'list',
			'default' => array(
				'chroot-everyone',
				'prohibit-dot-files-read',
				'prohibit-dot-files-write',
				'no-anonymous',
				'customer-proof'
			)
		),
		'auth_config_name_mysql' => 'string',
		'auth_config_name_pgsql' => 'string',
		'auth_config_name_ldap' => 'string',
		'auth_config_name_puredb' => 'string',
		'authentication_url_mysql' => 'string',
		'authentication_url_pgsql' => 'string',
		'authentication_url_puredb' => 'string',
		'authentication_url' => 'string',
		'ftpusers_table_mysql' => 'string',
		'ftpusers_table_pgsql' => 'string',
		'ftpusers_table_puredb' => 'string',
		'ftpusers_table' => array(
			'type' => 'string',
			'default' => 'ftpusers'
		)
	);
	private static $available_auths = array(
		'mysql' => array(
			'auth_file' => true,
			'url' => 'DB_URL'
		),
		'unix' => array(),
		'pgsql' => array(
			'auth_file' => true,
			'url' => 'DB_URL'
		),
		'pam' => array(),
		'ldap' => array(
			'auth_file' => true
		),
		'puredb' => array(
			'auth_file' => true,
			'auth_file_default' => 'pwd.pdb',
			'url' => 'DB_URL'
		)
	);
	private static $start_options = array(
		'chroot-everyone' => '-A',
		'daemonize' => '-B',
		'prohibit-dot-files-read' => '-X',
		'prohibit-dot-files-write' => '-x',
		'nat-mode' => '-N',
		'no-anonymous' => '-E',
		'customer-proof' => '-Z'
	);
	/**
	 * Authentications supported by Pure-FTPd
	 *
	 * @var array
	 */
	private $auths = null;
	
	/**
	 * Extra start up arguments
	 *
	 * @var array
	 */
	private $extra_args = array();
	
	/**
	 * Check configuration first, make sure everything is honky-dory
	 *
	 * @see Server_Feature::preconfigure()
	 */
	function preconfigure() {
		$db_urls = array();
		$option_name = __CLASS__ . '::authentications';
		$auths = $this->config->option_list($option_name, array(), " ");
		foreach ($auths as $auth) {
			if (!array_key_exists($auth, self::$available_auths)) {
				throw new Exception_Configuration($option_name, "{auth} is not one of {auth_types}", array(
					"auth" => $auth,
					"auth_types" => implode(",", array_keys(self::$available_auths))
				));
			}
			$this_auth = $auths[$auth];
			if (array_key_exists('url', $this_auth)) {
				$db_url_options = array(
					__CLASS__ . '::authentication_url_' . $auth,
					__CLASS__ . '::authentication_url'
				);
				$db_url_name = $this->config->first_option($db_url_options, $this_auth['url']);
				
				$db_table_options = array(
					__CLASS__ . '::ftpusers_table_' . $auth,
					__CLASS__ . '::ftpusers_table_'
				);
				
				$db_table_name = $this->config->first_option($db_table_options, avalue($this_auth, 'table'));
				$this_auth['url_name'] = $db_url_name;
				$url = $this->config->variable($db_url_name, "url");
				$parts = zesk\Database::url_parse($url);
				if (!$parts) {
					throw new Exception_Configuration($option_name, "{db_url_name} required by {class} should be a URL, but it's not ({url})", array(
						"db_url_name" => $db_url_name,
						"class" => get_class($this),
						"url" => $url
					));
				}
				$db_urls[$url] = $url;
				$parts['table'] = $db_table_name;
				$this_auth['map'] += $parts;
			}
			$this->auths[$auth] = $this_auth;
		}
		
		$option_name = __CLASS__ . '::start_options';
		$settings = $this->config->option_list($option_name, array(), " ");
		$this->extra_args = array();
		foreach ($settings as $setting) {
			if (!array_key_exists($setting, self::$start_options)) {
				throw new Exception_Configuration($option_name, "{setting} is not one of {auth_types}", array(
					"setting" => $setting,
					"start_options" => implode(",", array_keys(self::$start_options))
				));
			}
			$this->extra_args[] = self::$start_options[$setting];
		}
		
		$this->database_preconfigure(array_keys($db_urls));
	}
	
	/**
	 * Configure the server and restart daemons as appropriate
	 *
	 * @see Server_Feature::configure()
	 */
	function configure() {
		$name = 'pure-ftpd';
		$extra_args = implode(" ", $this->extra_args);
		$map = array(
			"NAME" => $name,
			"PUREFTPD_EXTRA_ARGS" => lists::append_unique($extra_args, "-B", " ")
		) + $this->options;
		$config_options = array();
		foreach ($this->auths as $auth => $settings) {
			if (!avalue($settings, 'auth_file')) {
				$config_options[] = "-l $auth";
				continue;
			}
			$file_name = $this->option('auth_config_name_' . $auth, avalue($settings, 'auth_file_default', "pureftpd-$auth.conf"));
			$this->configuration_files("pureftpd", $file_name, '/etc/', array(
				"owner" => "root:root",
				"mode" => 0600,
				"map" => $map + avalue($settings, 'map', array())
			));
			$config_options[] = "-l $auth:/etc/$file_name";
		}
		$map['AUTHENTICATIONS'] = implode(" ", $config_options);
		
		// PureFTPd is a configured package
		$dest = '/etc/init.d/';
		$this->configuration_files("pure-ftpd", 'init.d-pureftpd.sh', path($dest, $name), array(
			"owner" => "root:root",
			"mode" => 0700,
			"map" => $map,
			"changed" => "restart_service"
		));
	}
	
	/**
	 * Called after configure if changed
	 */
	function hook_configure_changed_restart_service() {
		$this->restart_service("pure-ftpd");
	}
} 
