<?php
/**
 *
 */
use zesk\Application;
use zesk\Options;
use zesk\Cache;
use zesk\arr;
use zesk\Net_HTTP_Client;

/**
 * Collect, store, and manage EC2 Awareness meta data
 *
 * @copyright &copy; 2016 Market Acumen, Inc.
 * @author kent
 *
 */
class AWS_EC2_Awareness extends Options {

	/**
	 *
	 * @var Application
	 */
	public $application = null;

	/**
	 * Root URL to retrieve the settings from the network
	 */
	private static $url = "http://169.254.169.254/latest/";

	/**
	 *
	 * @var string
	 */
	const setting_hostname = "hostname";
	/**
	 *
	 * @var string
	 */
	const setting_instance_id = "instance_id";
	/**
	 *
	 * @var string
	 */
	const setting_instance_type = "instance_type";
	/**
	 *
	 * @var string
	 */
	const setting_local_hostname = "local_hostname";
	/**
	 *
	 * @var string
	 */
	const setting_local_ipv4 = "local_ipv4";
	/**
	 *
	 * @var string
	 */
	const setting_mac = "mac";
	/**
	 *
	 * @var string
	 */
	const setting_public_hostname = "public_hostname";
	/**
	 *
	 * @var string
	 */
	const setting_public_ipv4 = "public_ipv4";
	/**
	 *
	 * @var string
	 */
	const setting_security_groups = "security_groups";

	/**
	 *
	 * @var integer
	 */
	const default_cache_expire_seconds = 600; // 10 Minutes

	/**
	 *
	 * @var Cache
	 */
	protected $cache = null;

	/**
	 * Mock settings for development (fakes it as best it can)
	 *
	 * @var array
	 */
	private $mock_settings = null;

	/**
	 *
	 * @var array
	 */
	private static $setting_to_suffix = array(
		self::setting_hostname => "hostname",
		self::setting_instance_id => "instance-id",
		self::setting_instance_type => "instance-type",
		self::setting_local_hostname => "local-hostname",
		self::setting_local_ipv4 => "local-ipv4",
		self::setting_mac => "mac",
		self::setting_public_hostname => "public-hostname",
		self::setting_public_ipv4 => "public-ipv4",
		self::setting_security_groups => "security-groups"
	);

	/**
	 * Create a new AWS_EC2_Awareness
	 *
	 * @param array $options
	 */
	public function __construct(Application $application, array $options = null) {
		parent::__construct($options);
		$this->application = $application;
		$this->inherit_global_options();
		$this->cache = Cache::register(__CLASS__)->expire_after($this->option("cache_expire_seconds", self::default_cache_expire_seconds));
	}

	/**
	 *
	 * @return string
	 */
	public function instance_id() {
		return $this->get(self::setting_instance_id);
	}

	/**
	 *
	 * @return string
	 */
	public function local_ipv4() {
		return $this->get(self::setting_local_ipv4);
	}

	/**
	 *
	 * @return string
	 */
	public function public_ipv4() {
		return $this->get(self::setting_public_ipv4);
	}
	/*
	 * As of 2013-07-19: <pre> ami-id ami-launch-index ami-manifest-path block-device-mapping/ hostname instance-action instance-id instance-type kernel-id local-hostname local-ipv4 mac metrics/ network/ placement/ profile public-ipv4 public-keys/ reservation-id security-groups </pre>
	 */
	public function get($mixed = null) {
		if ($mixed === null) {
			$mixed = array_keys(self::$setting_to_suffix);
		}
		if (is_array($mixed)) {
			$result = array();
			foreach ($mixed as $k) {
				$result[$k] = $this->get($k);
			}
			return $result;
		}
		if (!is_string($mixed)) {
			return null;
		}
		$suffix = avalue(self::$setting_to_suffix, $mixed);
		$cache = $this->cache;
		if ($cache->has($suffix)) {
			return $cache->get($suffix);
		}
		$cache->set($suffix, $result = $this->fetch($suffix));
		return $result;
	}
	private function mock_settings() {
		if ($this->mock_settings !== null) {
			return $this->mock_settings;
		}
		$host = php_uname('n');
		$ips = array_values(arr::clean(zesk\System::ip_addresses($this->application), "127.0.0.1"));
		$macs = array_values(zesk\System::mac_addresses($this->application));

		$settings = array(
			self::setting_hostname => $host,
			self::setting_instance_id => "i-ffffffff",
			self::setting_instance_type => "mock",
			self::setting_local_hostname => $host,
			self::setting_local_ipv4 => first($ips),
			self::setting_mac => first($macs),
			self::setting_public_hostname => $host,
			self::setting_public_ipv4 => last($ips),
			self::setting_security_groups => "mock-security-group"
		);
		foreach ($settings as $setting => $value) {
			if ($this->has_option("mock_" . $setting)) {
				$settings[$setting] = $this->option("mock_" . $setting);
			}
		}
		$settings = arr::map_keys($settings, self::$setting_to_suffix);
		return $this->mock_settings = $settings;
	}
	/**
	 *
	 * @param string $uri
	 *        	Field to retrieve
	 * @return string
	 */
	private function fetch($suffix) {
		if ($this->option_bool("mock")) {
			return avalue($this->mock_settings(), $suffix);
		}
		$url = glue(self::$url, "/", "meta-data/$suffix");
		$result = null;
		if (to_bool(ini_get("allow_url_fopen"))) {
			$result = file_get_contents($url);
		} else {
			$client = new Net_HTTP_Client($this->application, $url);
			$result = $client->go();
			unset($client);
		}
		return $result;
	}
}

