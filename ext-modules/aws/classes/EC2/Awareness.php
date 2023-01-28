<?php declare(strict_types=1);

/**
 *
 */
namespace aws\classes\EC2;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Cache;
use zesk\Hookable;
use zesk\Net_HTTP_Client;
use zesk\System;

/**
 * Collect, store, and manage EC2 Awareness meta data
 *
 * @copyright &copy; 2023, Market Acumen, Inc.
 * @author kent
 *
 */
class Awareness extends Hookable {
	/**
	 *
	 * @var Application
	 */
	public $application = null;

	/**
	 * Root URL to retrieve the settings from the network
	 */
	private static string $url = 'http://169.254.169.254/latest/';

	/**
	 *
	 * @var string
	 */
	public const setting_hostname = 'hostname';

	/**
	 *
	 * @var string
	 */
	public const setting_instance_id = 'instance_id';

	/**
	 *
	 * @var string
	 */
	public const setting_instance_type = 'instance_type';

	/**
	 *
	 * @var string
	 */
	public const setting_local_hostname = 'local_hostname';

	/**
	 *
	 * @var string
	 */
	public const setting_local_ipv4 = 'local_ipv4';

	/**
	 *
	 * @var string
	 */
	public const setting_mac = 'mac';

	/**
	 *
	 * @var string
	 */
	public const setting_public_hostname = 'public_hostname';

	/**
	 *
	 * @var string
	 */
	public const setting_public_ipv4 = 'public_ipv4';

	/**
	 *
	 * @var string
	 */
	public const setting_security_groups = 'security_groups';

	/**
	 *
	 * @var integer
	 */
	public const default_cache_expire_seconds = 600; // 10 Minutes

	/**
	 *
	 * @var CacheItemInterface
	 */
	protected ?CacheItemInterface $cache = null;

	/**
	 * Mock settings for development (fakes it as best it can)
	 *
	 * @var array
	 */
	private array $mock_settings = [];

	/**
	 *
	 * @var array
	 */
	private static array $setting_to_suffix = [
		self::setting_hostname => 'hostname',
		self::setting_instance_id => 'instance-id',
		self::setting_instance_type => 'instance-type',
		self::setting_local_hostname => 'local-hostname',
		self::setting_local_ipv4 => 'local-ipv4',
		self::setting_mac => 'mac',
		self::setting_public_hostname => 'public-hostname',
		self::setting_public_ipv4 => 'public-ipv4',
		self::setting_security_groups => 'security-groups',
	];

	/**
	 * Create a new AWS_EC2_Awareness
	 *
	 * @param Application $application
	 * @param array $options
	 * @throws InvalidArgumentException
	 */
	public function __construct(Application $application, array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration();

		try {
			$this->cache = $this->application->cacheItemPool()->getItem(__CLASS__);
		} catch (\InvalidArgumentException) {
			$this->cache = null;
		}
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
	 * As of 2013-07-19:
	 * <pre>
	 * ami-id
	 * ami-launch-index
	 * ami-manifest-path
	 * block-device-mapping/
	 * hostname
	 * instance-action
	 * instance-id
	 * instance-type
	 * kernel-id
	 * local-hostname
	 * local-ipv4
	 * mac
	 * metrics/
	 * network/
	 * placement/
	 * profile
	 * public-ipv4
	 * public-keys/
	 * reservation-id
	 * security-groups
	 * </pre>
	 */
	public function get($mixed = null) {
		if ($mixed === null) {
			$mixed = array_keys(self::$setting_to_suffix);
		}
		if (is_array($mixed)) {
			$result = [];
			foreach ($mixed as $k) {
				$result[$k] = $this->get($k);
			}
			return $result;
		}
		if (!is_string($mixed)) {
			return null;
		}
		$suffix = self::$setting_to_suffix[$mixed] ?? null;
		$cache = $this->cache;
		$values = toArray($cache->get());
		if (!array_key_exists($suffix, $values)) {
			$values[$suffix] = $this->fetch($suffix);
			$cache->set($values);
		}
		return $values[$suffix];
	}

	/**
	 * Enable mock settings for AWS when a configuration flag is set
	 *
	 * @return array
	 */
	private function mock_settings() {
		if ($this->mock_settings !== null) {
			return $this->mock_settings;
		}
		$host = php_uname('n');
		$ips = array_values(ArrayTools::clean(System::ip_addresses($this->application), '127.0.0.1'));
		$macs = array_values(System::mac_addresses($this->application));

		$settings = [
			self::setting_hostname => $host,
			self::setting_instance_id => 'i-ffffffff',
			self::setting_instance_type => 'mock',
			self::setting_local_hostname => $host,
			self::setting_local_ipv4 => first($ips),
			self::setting_mac => first($macs),
			self::setting_public_hostname => $host,
			self::setting_public_ipv4 => last($ips),
			self::setting_security_groups => 'mock-security-group',
		];
		foreach ($settings as $setting => $value) {
			if ($this->hasOption('mock_' . $setting)) {
				$settings[$setting] = $this->option('mock_' . $setting);
			}
		}
		$settings = ArrayTools::keysMap($settings, self::$setting_to_suffix);
		return $this->mock_settings = $settings;
	}

	/**
	 *
	 * @param string $uri
	 *        	Field to retrieve
	 * @return string
	 */
	private function fetch($suffix) {
		if ($this->optionBool('mock')) {
			return $this->mock_settings()[$suffix] ?? null;
		}
		$url = glue(self::$url, '/', "meta-data/$suffix");
		$result = null;
		if (toBool(ini_get('allow_url_fopen'))) {
			$result = file_get_contents($url);
		} else {
			$client = new Net_HTTP_Client($this->application, $url);
			$result = $client->go();
			unset($client);
		}
		return $result;
	}
}
