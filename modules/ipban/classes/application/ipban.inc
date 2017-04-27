<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
use zesk\Timestamp;
use zesk\arr;
use zesk\URL;
use zesk\File;
use zesk\Text;
use zesk\Exception_Configuration;
use zesk\JSON;
use zesk\str;
use zesk\IPv4;
use zesk\Process_Mock;
use zesk\FIFO;
use zesk\Net_Sync;

/**
 * IP Banning application
 *
 * Actively bans misbehaving IPs
 *
 * @author kent
 * @package zesk
 * @subpackage IPBan
 * @copyright (C) 2013 Market Acumen, Inc.
 */
class Application_IPBan extends zesk\Application {
	
	/**
	 * 
	 * @var string
	 */
	const snippet_match = '/var/db/ipban/ipban.inc';
	
	/**
	 * 
	 * @var string
	 */
	const snippet = "if (file_exists('/var/db/ipban/ipban.inc')) {\n\trequire_once '/var/db/ipban/ipban.inc';\n}\n";
	
	/**
	 * Debugging logging enabled
	 *
	 * @var boolean
	 */
	public $debug = false;
	
	/**
	 *
	 * @var zesk\Interface_Process
	 */
	protected $proc = null;
	
	/**
	 *
	 * @var FIFO
	 */
	protected $fifo = null;
	
	/**
	 * iptables command path
	 *
	 * @var string
	 */
	protected $iptables = null;
	
	/**
	 * Name for Toxic IPs (from outside source)
	 *
	 * @var string
	 */
	const chain_toxic = 'zesk-ipban-toxic';
	
	/**
	 * Name for dynamically updated IPs
	 *
	 * @var string
	 */
	const chain_ban = 'zesk-ipban';
	
	/**
	 * Chains structure
	 *
	 * @var array
	 */
	protected $chains = array();
	
	/**
	 * IP address => IP address
	 *
	 * @var array
	 */
	protected $whitelist = array();
	
	/**
	 * Whitelist mod time
	 *
	 * @var integer
	 */
	protected $whitelist_file_mtime = null;
	
	/**
	 * IP address => array($chain_path => $rule, $chain_path => $rule)
	 *
	 * @var unknown
	 */
	protected $ips = array();
	
	/**
	 * Chains structure
	 *
	 * @var boolean
	 */
	protected $chains_dirty = true;
	
	/**
	 * Last Database check
	 *
	 * @var string
	 */
	protected $last_check = null;
	
	/**
	 *
	 * @var IPBan_FS
	 */
	protected $ipban_fs = null;
	
	/**
	 * Standard chain configuration
	 *
	 * @var unknown
	 */
	static $chain_config = array(
		'INPUT' => array(
			'suffix' => '-input',
			'ip_column' => 'source',
			'command_parameter' => '-s'
		),
		'OUTPUT' => array(
			'suffix' => '-output',
			'ip_column' => 'destination',
			'command_parameter' => '-d'
		)
	);
	public static function default_configuration() {
		return array(
// 			'zesk::run_path' => '/var/run/ipban',
// 			'zesk::data_path' => '/var/run/ipban',
			'zesk\\Module_Logger_File::defaults::time_zone' => 'UTC',
			'zesk\\Module_Logger_File::files::main::linkname' => 'ipban.log',
			'zesk\\Module_Logger_File::files::main::name' => '/var/log/ipban/{YYYY}-{MM}-{DD}-ipban.log',
			'IPBan_FS::path' => '/var/db/ipban',
			'zesk\\Database::names::default' => "mysqli://ipban:ipban@localhost/ipban",
			'zesk\\Database::default' => "default"
		);
	}
	protected $register_hooks = array(
		'Settings'
	);
	protected $load_modules = array(
		'ipban',
		'parse_log',
		'server',
		'mysql',
		'cron'
	);
	
	/**
	 * Application::preconfigure
	 *
	 * @param array $options
	 */
	public function preconfigure(array $options) {
		date_default_timezone_set("UTC");
		umask(0);
		$path = $options['path'] = '/etc/ipban/';
		$file = $options['file'] = 'ipban.json';
		if (is_dir($path)) {
			$conf = path($path, $file);
			if (!is_file($conf)) {
				if (!@file_put_contents($conf, JSON::encode_pretty(self::default_configuration()))) {
					$this->logger->warning("Can not write {conf}", compact("conf"));
					$this->configuration->paths_set(self::default_configuration());
				}
			}
		} else {
			$this->logger->warning("No configuration directory {path} or {file}", compact("path", "file"));
		}
		$path = 'Application_IPBan::url_toxic_ip';
		if (!$this->configuration->path_exists($path)) {
			$this->configuration->path_set($path, 'http://www.stopforumspam.com/downloads/toxic_ip_cidr.txt');
		}
		return $options;
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see \zesk\Application::postconfigure()
	 */
	public function postconfigure() {
		try {
			$this->ipban_fs = new IPBan_FS();
		} catch (Exception_Configuration $e) {
			$this->logger->notice("IPBan_FS not configured. {e}", array(
				"e" => $e
			));
		}
	}
	/**
	 * Construct the object
	 */
	protected function hook_construct() {
		$this->inherit_global_options();
		$this->debug = $this->option_bool('debug');
		if ($this->debug) {
			$this->logger->debug("{class} debugging enabled", array(
				"class" => __CLASS__
			));
		}
	}
	
	/**
	 * Load an IP file
	 *
	 * @param string $path
	 * @param string $purpose
	 * @return array
	 */
	private function load_ip_file($path, $purpose = "IP") {
		$this->logger->notice("Loading {purpose} file: {path}", compact("path", "purpose"));
		$contents = file::contents($path, "");
		$contents = Text::remove_line_comments($contents, "#", false);
		$lines = arr::trim_clean(explode("\n", $contents));
		$ips = $this->normalize_ips($lines, $purpose);
		return $ips;
	}
	
	/**
	 * Strip bad IPs and masks from the file
	 *
	 * @param array $ips
	 * @return Ambigous <unknown, string>
	 */
	private function normalize_ips(array $ips, $purpose = null) {
		foreach ($ips as $index => $ip) {
			if (IPv4::is_mask($ip)) {
				$ips[$index] = str::unsuffix($ip, "/32");
			} else if (!IPv4::valid($ip)) {
				unset($ips[$index]);
				$this->logger->debug("normalize_ips: Removed {ip} from {purpose}", compact("ip", "purpose"));
			}
		}
		return $ips;
	}
	
	/**
	 * File containing toxic IPs
	 *
	 * @return string
	 */
	public function toxic_ip_path() {
		return $this->option('toxic_ip_path', '/etc/ipban/toxic_ip');
	}
	
	/**
	 * File containing whitelist IPs (never ban)
	 *
	 * @return string
	 */
	public function whitelist_ip_path() {
		return $this->option('whitelist_ip_path', '/etc/ipban/whitelist');
	}
	
	/**
	 * File containing blacklist IPs (always ban)
	 *
	 * @return string
	 */
	public function blacklist_ip_path() {
		return $this->option('blacklist_ip_path', '/etc/ipban/blacklist');
	}
	
	/**
	 * hook_classes
	 *
	 * @param array $classes
	 * @return string
	 */
	protected function hook_classes(array $classes) {
		$classes[] = "IPBan";
		$classes[] = "Settings";
		return $classes;
	}
	
	/**
	 * Implement Application::daemon()
	 *
	 * @param zesk\Interface_Process $p
	 * @return string number
	 */
	public static function daemon(zesk\Interface_Process $p) {
		try {
			self::check_permissions();
		} catch (Exception $e) {
			$p->log($e->getMessage());
			return "down";
		}
		$application = Application::instance();
		/* @var $application Application_IPBan */
		return $application->daemon_loop($p);
	}
	
	/**
	 * Run cron on a daily basis
	 */
	public static function cron_day() {
		/* @var $app Application_IPBan */
		$app = self::instance();
		$app->sync_toxic_ips();
	}
	
	/**
	 * Run cron on a hourly basis
	 */
	public static function cron_hour() {
		/* @var $app Application_IPBan */
		$app = self::instance();
		$app->check_instrumented_files();
	}
	
	/**
	 * Test Daemon
	 *
	 * @param number $nseconds
	 * @return string
	 */
	public static function test($nseconds = 60) {
		$p = new Process_Mock(array(
			"quit_after" => $nseconds
		));
		return self::daemon($p);
	}
	
	/**
	 * Scan and make sure IPban is instrumented on files in the system.
	 *
	 * Useful for detecting/fixing issues with self-updating software
	 *
	 * Set in your configuration file:
	 *
	 * Application_IPBan::ipban_files=["/path/to/index.php","/path/to/wordpress/index.php","/path/to/drupal/index.php"]
	 */
	public function check_instrumented_files() {
		$files = $this->option_list("ipban_files");
		
		if (count($files) === 0) {
			$this->logger->notice("No ipban_file specified for instrumentation.");
		}
		$checked = $updated = $failed = 0;
		foreach ($files as $file) {
			$params = array(
				"class" => get_class($this),
				"file" => $file
			);
			$checked++;
			if (!is_readable($file)) {
				$this->logger->warning("{class}::check_instrumented_files {file} does not exist or is not readable", $params);
				++$failed;
			} else {
				$contents = file_get_contents($file);
				if (strpos($contents, self::snippet_match) !== false) {
					$this->logger->debug("{class}::check_instrumented_files {file} is instrumented", $params);
				} else {
					$found = null;
					foreach (array(
						'<?php',
						'<?'
					) as $tag) {
						if (strpos($contents, $tag) !== false) {
							$found = $tag;
							break;
						}
					}
					if (!$found) {
						$this->logger->error("{class}::check_instrumented_files {file} is not writable", array(
							"class" => get_class($this),
							"file" => $file
						));
					} else {
						if (!is_writable($file)) {
							$this->logger->error("{class}::check_instrumented_files {file} is not writable", $params);
							++$failed;
						} else {
							$contents = implode($tag . "\n" . self::snippet, explode($tag, $contents, 2));
							file_put_contents($file, $contents);
							$this->logger->notice("{class}::check_instrumented_files {file} was updated with ipban snippet", $params);
							$updated++;
						}
					}
				}
			}
		}
		$this->logger->notice("{class}::check_instrumented_files - {checked} checked, {updated} updated, {failed} failed", array(
			"class" => get_class($this),
			"checked" => $checked,
			"failed" => $failed,
			"updated" => $updated
		));
	}
	public function sync_whitelist() {
		$whitelist_file = $this->whitelist_ip_path();
		if (!is_file($whitelist_file)) {
			return null;
		}
		clearstatcache(true, $whitelist_file);
		$mtime = filemtime($whitelist_file);
		if ($mtime !== $this->whitelist_file_mtime) {
			$this->whitelist = arr::flip_copy($this->load_ip_file($whitelist_file, "whitelist"));
			$this->whitelist_file_mtime = $mtime;
			if (count($this->whitelist)) {
				$this->logger->notice("Whitelisted IPs: {ips}", array(
					"ips" => implode(", ", $this->whitelist)
				));
			}
			return true;
		}
		return false;
	}
	/**
	 * Main daemon loop
	 *
	 * @param zesk\Interface_Process $p
	 * @return number
	 */
	public function daemon_loop(zesk\Interface_Process $p) {
		$seconds = $this->option("daemon_loop_sleep", 1);
		$this->logger->notice("Daemon loop sleep seconds: {seconds}", array(
			"seconds" => $seconds
		));
		declare(ticks = 1) {
			if (!$this->option_bool("no_fifo")) {
				$this->fifo = self::fifo(true);
				$this->logger->debug("Created fifo {path}", array(
					"path" => $this->fifo->path()
				));
			}
			$this->proc = $p;
			$this->init_chains();
			$this->sync_toxic_ips(true);
			$this->sync_whitelist();
			$this->initial_ban();
			$this->logger->debug("Entering main loop ...");
			while (!$this->proc->done()) {
				$this->handle_fifo();
				$this->handle_db();
				$this->proc->sleep($seconds);
				$this->sync_toxic_ips();
				$this->sync_whitelist();
			}
		}
		return 0;
	}
	private function initial_ban() {
		$this->last_check = Timestamp::now();
		$ips = IPBan::ban_since(null, $this->option());
		$this->drop_ip(self::chain_ban, $ips);
	}
	/**
	 * Do basic sanity checks prior to launching daemon completely.
	 *
	 * @throws Exception_Configuration
	 */
	private static function check_permissions() {
		global $zesk;
		/* @var $zesk \zesk\Kernel */
		if (($iptables = $zesk->paths->which("iptables")) === null) {
			throw new Exception_Configuration("path", "IP tables is not installed in {path}", array(
				"path" => implode(":", $zesk->paths->command())
			));
		}
		try {
			$this->process->execute("$iptables --list=INPUT -n");
		} catch (\zesk\Exception_Command $e) {
			throw new Exception_Configuration("user", "You must be root to run Application_IPBan");
		}
	}
	
	/**
	 * Retrieve the FIFO to communicate with the server
	 *
	 * @return FIFO
	 */
	public static function fifo($create = false) {
		$configuration = zesk()->configuration->pave("Application_IPBan");
		$path = $configuration->get('fifo_path', 'ipban.fifo');
		$mode = $configuration->get('fifo_mode', 0666);
		return new FIFO($path, $create, $mode);
	}
	
	/**
	 * Receive messages from the FIFO
	 */
	public function handle_fifo() {
		if ($this->option_bool("no_fifo")) {
			return;
		}
		$timeout = $this->option_integer('timeout', 5);
		$result = $this->fifo->read($timeout);
		if ($result === array()) {
			return;
		}
		$this->logger->debug("Received message: {data}", array(
			"data" => serialize($result)
		));
	}
	
	/**
	 * Receive updates from the database
	 */
	public function handle_db() {
		if ($this->option_bool("no_database")) {
			return;
		}
		$this->logger->debug(__CLASS__ . "::handle_db");
		$options = $this->option();
		
		$now = Timestamp::now();
		$ban_ips = IPBan::ban_since($this->last_check, $options);
		$ban_ips += IPBan_IP::blacklist($this->last_check);
		$allow_ips = IPBan::allow_since($this->last_check, $options);
		$allow_ips += IPBan_IP::whitelist($this->last_check) + $this->whitelist;
		
		foreach ($ban_ips as $ip => $ip) {
			if (array_key_exists($ip, $allow_ips)) {
				unset($ban_ips[$ip]);
			}
		}
		if ($this->last_check === null) {
			$this->sync_ip_list(self::chain_ban, $ban_ips);
		} else {
			$this->drop_ip(self::chain_ban, $ban_ips);
			$this->allow_ip(self::chain_ban, $allow_ips);
		}
		$this->last_check = $now;
		// 		var_dump("ban_ips", $ban_ips);
		// 		var_dump("allow_ips", $allow_ips);
	}
	
	/**
	 * Support whitelisting entire networks
	 *
	 * @param string $network
	 * @param array $ips
	 * @return array
	 */
	private static function remove_masked_ips($network, array $ips, $purpose = null) {
		foreach ($ips as $key => $ip) {
			if (IPv4::within_network($ip, $network)) {
				$this->logger->debug("Removed {ip} from list - within {purpose} network {network}", compact("ip", "network", "purpose"));
				unset($ips[$key]);
			}
		}
		return $ips;
	}
	
	/**
	 * Filter whitelist
	 *
	 * @param array $ips
	 */
	protected function filter_whitelist(array $ips) {
		$ips = arr::flip_copy($ips, false);
		foreach ($this->whitelist as $whiteip) {
			if (IPv4::is_mask($whiteip)) {
				$ips = self::remove_masked_ips($whiteip, $ips, "whitelist");
			} else if (array_key_exists($whiteip, $ips)) {
				unset($ips[$whiteip]);
			}
		}
		return array_values($ips);
	}
	
	/**
	 * Retrieve all IPs associated with a chain
	 *
	 * @param string $name
	 * @return array
	 */
	protected function chain_ips($prefix) {
		$ips = array();
		foreach (self::$chain_config as $settings) {
			$suffix = $ip_column = null;
			extract($settings, EXTR_IF_EXISTS);
			$rules = apath($this->chains, array(
				$prefix . $suffix,
				"rules"
			));
			if (!is_array($rules)) {
				continue;
			}
			foreach ($rules as $rule) {
				$ip = $rule[$ip_column];
				$ips[$ip] = $ip;
			}
		}
		return $ips;
	}
	
	/**
	 * Does this chain exist?
	 *
	 * @param string $name
	 * @return boolean
	 */
	protected function has_chain($name) {
		return array_key_exists($name, $this->chains);
	}
	
	/**
	 * Does the $from chain have a link to the $to chain?
	 *
	 * @param string $from
	 * @param string $to
	 * @return boolean
	 */
	protected function chain_links_to($from, $to) {
		$chain = apath($this->chains, array(
			$from,
			"rules"
		), array());
		foreach ($chain as $rule_number => $rule) {
			$target = avalue($rule, 'target');
			if ($target === $to) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Initialize the chains, setup our internal chains
	 */
	protected function init_chains() {
		$this->iptables = zesk()->paths->which("iptables");
		$this->clean();
		$this->logger->notice("Existing chains: " . implode(", ", array_keys($this->chains)));
		foreach (array(
			self::chain_toxic,
			self::chain_ban
		) as $prefix) {
			foreach (self::$chain_config as $parent => $data) {
				$name = $prefix . $data['suffix'];
				if (!$this->has_chain($name)) {
					$this->logger->notice("Adding chain {name}", compact("name"));
					$this->iptables('--new {0}', $name);
				}
				if (!$this->chain_links_to($parent, $name)) {
					$this->logger->notice("Linking chain {name} to chain {parent}", compact("name", "parent"));
					$this->iptables('--insert {0} 1 -j {1}', $parent, $name);
				}
			}
		}
	}
	/**
	 * Find IP addresses in current rules
	 *
	 * @param array $ips
	 * @return array
	 */
	protected function find_ips(array $ips) {
		$found = array();
		foreach ($ips as $ip) {
			if (self::null_ip($ip)) {
				continue;
			}
			$rules = avalue($this->ips, $ip);
			if (!$rules) {
				if (!IPv4::is_mask($ip) && !IPv4::valid($ip)) {
					$this->logger->error("Strange IP address: {ip}", array(
						"ip" => $ip
					));
				}
				continue;
			}
			$found = array_merge($found, array_values($rules));
		}
		return $found;
	}
	
	/**
	 * Does the named chain contain the IP address already?
	 *
	 * @param string $name
	 *        	FULL chain name (not prefix)
	 * @param string $ip
	 *        	IP address
	 * @return boolean
	 */
	protected function chain_has_ip($name, $ip) {
		$records = avalue($this->ips, $ip);
		if (!is_array($records)) {
			return false;
		}
		$keys = array_keys($records);
		foreach ($keys as $key) {
			if (begins($key, "$name.")) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Add an IP to the blocked IP list specified by prefix
	 *
	 * @param unknown $prefix
	 * @param array $ips
	 */
	protected function drop_ip($prefix, array $ips) {
		if (count($ips) === 0) {
			return 0;
		}
		//$this->logger->notice("DROPPING {ips}", array("ips" => $ips));
		$this->clean();
		$ips = $this->filter_whitelist($ips);
		
		if ($this->ipban_fs) {
			$this->ipban_fs->drop_ip($ips);
		}
		$added = 0;
		foreach (self::$chain_config as $chain => $settings) {
			$suffix = $command_parameter = null;
			extract($settings, EXTR_IF_EXISTS);
			$name = $prefix . $suffix;
			foreach ($ips as $ip) {
				if ($this->chain_has_ip($name, $ip)) {
					continue;
				}
				$this->logger->notice("Blocking {ip} in {name}", array(
					"ip" => $ip,
					"name" => $chain
				));
				$this->iptables("-A {0} $command_parameter {1} -j DROP", $name, $ip);
				$added = $added + 1;
			}
		}
		
		$this->chains_dirty = true;
		return $added;
	}
	
	/**
	 * Remove IPs from named filter prefix (should be self::chain_FOO)
	 *
	 * @param string $prefix
	 *        	One of self::chain_FOO
	 * @param array $ips
	 *        	Array of IPs to allow
	 */
	protected function allow_ip($prefix, array $ips) {
		if (count($ips) === 0) {
			return 0;
		}
		$this->clean();
		if ($this->ipban_fs) {
			$this->ipban_fs->allow_ip($ips);
		}
		
		$found = $this->find_ips($ips);
		if (count($found) === 0) {
			$this->logger->debug("No entries found for ips: {ips}", array(
				"ips" => implode(", ", $ips)
			));
			return 0;
		}
		$indexes = array();
		foreach ($found as $k => $rule) {
			$indexes[$k] = $rule['index'];
		}
		array_multisort($indexes, SORT_DESC | SORT_NUMERIC, $found);
		foreach ($found as $rule) {
			$this->logger->notice("Removing index {index} from {name} {source}->{destination}", $rule);
			$this->iptables('-D {0} {1}', $rule['name'], $rule['index']);
			$this->chains_dirty = true;
		}
		return count($found);
	}
	
	/**
	 * Synchronize the toxic IP list
	 *
	 * @param string $force
	 * @return string
	 */
	public function sync_toxic_ips($force = false) {
		static $errored = false;
		$url = $this->option('url_toxic_ip');
		if (!URL::valid($url)) {
			if (!$errored) {
				$this->logger->error("Application_IPBan::url_toxic_ip not set to valid URL: {url}", array(
					"url" => $url
				));
				$errored = true;
			}
			return "config";
		}
		$path = $this->toxic_ip_path();
		$changed = Net_Sync::url_to_file($url, $path);
		if ($changed || $force) {
			if (!is_file($path)) {
				return "no-file";
			}
			$ips = $this->load_ip_file($path, "toxic");
			$ips = $this->filter_whitelist($ips);
			// 			$this->clean();
			// 			$chain_ips = $this->chain_ips(self::chain_toxic);
			$this->sync_ip_list(self::chain_toxic, $ips);
			return "synced";
		}
		return "unchanged";
	}
	
	/**
	 * Synchronize an IP list with a chain pair
	 *
	 * @param unknown $prefix
	 * @param array $ips
	 */
	protected function sync_ip_list($prefix, array $ips) {
		$this->clean();
		$this->remove_duplicates($prefix);
		$this->clean();
		
		if ($this->ipban_fs) {
			$this->ipban_fs->drop($ips);
		}
		$chain_ips = $this->chain_ips($prefix);
		
		$drop_ips = array();
		$allow_ips = array();
		
		if ($this->debug) {
			$this->logger->debug("IP list: {ips}", array(
				"ips" => _dump($ips)
			));
			$this->logger->debug("CHAIN IPs: {ips}", array(
				"ips" => _dump($chain_ips)
			));
		}
		foreach ($ips as $ip) {
			if (!array_key_exists($ip, $chain_ips)) {
				$drop_ips[] = $ip;
			}
			if (array_key_exists($ip, $chain_ips)) {
				unset($chain_ips[$ip]);
			}
		}
		if ($this->debug) {
			$this->logger->debug("ALLOW IPs: {ips}", array(
				"ips" => _dump($chain_ips)
			));
			$this->logger->debug("DROP IPs: {ips}", array(
				"ips" => _dump($drop_ips)
			));
		}
		$this->allow_ip($prefix, $chain_ips);
		$this->drop_ip($prefix, $drop_ips);
	}
	
	/**
	 * Remove duplicate IPs from list - this happens during development, so might as well keep it
	 * robust
	 *
	 * @param string $prefix
	 */
	protected function remove_duplicates($prefix) {
		foreach (self::$chain_config as $settings) {
			$suffix = $ip_column = null;
			extract($settings, EXTR_IF_EXISTS);
			$name = $prefix . $suffix;
			
			$rules = apath($this->chains, array(
				$name,
				"rules"
			));
			if (!is_array($rules)) {
				$this->logger->debug("remove_duplicates: No rules for {name}", compact("name"));
				continue;
			}
			$remove_indexes = array();
			$found_ips = array();
			foreach ($rules as $rule) {
				$ip = $rule[$ip_column];
				if (array_key_exists($ip, $found_ips)) {
					$remove_indexes[$rule['index']] = $ip;
				} else {
					$found_ips[$ip] = $ip;
				}
			}
			if (count($remove_indexes) > 0) {
				krsort($remove_indexes, SORT_NUMERIC | SORT_DESC);
				foreach ($remove_indexes as $index => $ip) {
					$this->logger->debug("Removing duplicate IP {ip} at index {index}", compact("ip", "index"));
					$this->iptables("-D {0} {1}", $name, $index);
				}
				$this->chains_dirty = true;
			}
		}
	}
	
	/**
	 * Clean the chains database from the iptables command
	 */
	protected function clean() {
		if ($this->chains_dirty) {
			$this->logger->debug("Cleaning");
			$this->chains = $this->list_chains();
			$this->ips = $this->ip_index($this->chains);
			$this->chains_dirty = false;
		}
	}
	
	/**
	 * Is this an empty IP address (or all)
	 *
	 * @param string $ip
	 * @return boolean
	 */
	private static function null_ip($ip) {
		return begins($ip, '0.0.0.0') || empty($ip);
	}
	
	/**
	 * Compute index by IP address, ignoring null IPs
	 *
	 * @param array $chains
	 * @return array
	 */
	private function ip_index(array $chains) {
		$ips = array();
		foreach ($chains as $name => $group) {
			$rules = $group['rules'];
			foreach ($rules as $index => $rule) {
				foreach (to_list("source;destination") as $k) {
					$ip = avalue($rule, $k);
					if (!self::null_ip($ip)) {
						$ips[$ip]["$name.rules.$index.$k"] = $rule;
					}
				}
			}
		}
		return $ips;
	}
	
	/**
	 * Get chains and parse them from the command iptables --list -n -v
	 *
	 * @return array
	 */
	protected function list_chains() {
		$result = $this->iptables("--list -n -v");
		return self::parse_list_chains($result);
	}
	
	/**
	 * Parse parenthesized chain line:
	 *
	 * <code>
	 * (policy ACCEPT)
	 * (policy ACCEPT 4555K packets, 1390M bytes)
	 * (1 references)
	 * </code>
	 *
	 * etc.
	 *
	 * @param string $string
	 * @return array
	 */
	protected function parse_chain_parens($string) {
		$matches = null;
		if (preg_match('/([0-9]+) references/', $string, $matches)) {
			return array(
				'references' => intval($matches[1]),
				'user' => true
			);
		}
		if (preg_match('/policy ([A-Za-z]+)(.*)/', $string, $matches)) {
			return array(
				'policy' => $matches[1],
				'user' => false,
				'stats' => trim($matches[2])
			);
		}
		$this->logger->warning("Unable to parse chain parens: {string}", array(
			"string" => $string
		));
		return array();
	}
	
	/**
	 * Parse --list -n output from iptables
	 *
	 * @param array $result
	 * @return array
	 */
	protected function parse_list_chains(array $result) {
		$chains = array();
		while (count($result) > 0) {
			$line = array_shift($result);
			if (preg_match('/Chain ([-A-Za-z0-9_]+) \(([^)]*)\)/', $line, $matches)) {
				$name = $matches[1];
				$chain_data = $this->parse_chain_parens($matches[2]);
				$line = array_shift($result);
				$headers = explode(" ", preg_replace('/\s+/', ' ', trim($line)));
				$rules = array();
				$rule_index = 1;
				while (count($result) > 0) {
					$line = trim(array_shift($result));
					if (empty($line)) {
						break;
					}
					$rule = array();
					$columns = explode(" ", preg_replace('/\s+/', ' ', $line));
					foreach ($columns as $index => $column) {
						$rule[$headers[$index]] = $column;
					}
					$rules[$rule_index] = $rule + array(
						'index' => $rule_index,
						'name' => $name
					);
					$rule_index++;
				}
				$chains[$name] = array(
					'rules' => $rules,
					'name' => $name
				) + $chain_data;
			} else {
				$this->logger->notice("Skipping line {line} - no chain match", array(
					"line" => $line
				));
			}
		}
		return $chains;
	}
	
	/**
	 * Run iptables command and return output
	 *
	 * @param string $parameters
	 * @return array
	 */
	protected function iptables($parameters) {
		$args = func_get_args();
		array_shift($args);
		return $this->process->execute_arguments($this->iptables . " " . $parameters, $args);
	}
}

