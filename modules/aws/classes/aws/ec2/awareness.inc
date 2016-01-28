<?php
class AWS_EC2_Awareness {
	/**
	 * Root URL to retrieve the settings from the network
	 */
	private static $url = "http://169.254.169.254/latest/";
	const setting_ServerID = "ServerID";
	const setting_ServerType = "ServerType";
	const setting_KernelID = "KernelID";
	const setting_LocalIP4 = "LocalIP4";
	const setting_LocalName = "LocalName";
	const setting_PublicIP4 = "PublicIP4";
	const setting_PublicName = "PublicName";
	const setting_UserData = "UserData";
	const setting_Zone = "Zone";
	private static $cached = array();
	private static $cached_times = array();
	public static $file_cache_path = null;
	public static $cache_expire_seconds = 600; // 10 Minutes
	private static $zesk_ec2_map = array(
		self::setting_LocalIP4 => "meta-data/local-ipv4",
		self::setting_LocalName => 'meta-data/local-hostname',
		self::setting_PublicIP4 => "meta-data/public-ipv4",
		self::setting_PublicName => "meta-data/hostname",
		self::setting_KernelID => 'meta-data/ami-id',
		'ami-launch-index' => 'meta-data/ami-launch-index',
		'ami-manifest-path' => 'meta-data/ami-manifest-path',
		self::setting_ServerID => 'meta-data/instance-id',
		self::setting_ServerType => 'meta-data/instance-type',
		self::setting_UserData => "user-data",
		"public-keys" => "meta-data/public-keys",
		"reservation-id" => "meta-data/reservation-id",
		self::setting_Zone => "meta-data/placement/availability-zone"
	);
	
	/*
	 * As of 2013-07-19: <pre> ami-id ami-launch-index ami-manifest-path block-device-mapping/ hostname instance-action instance-id instance-type kernel-id local-hostname local-ipv4 mac metrics/ network/ placement/ profile public-ipv4 public-keys/ reservation-id security-groups </pre>
	 */
	public static function get($mixed = null) {
		if ($mixed === null) {
			$mixed = array_keys(self::$zesk_ec2_map);
		}
		if (is_array($mixed)) {
			$result = array();
			foreach ($mixed as $k) {
				$result[$k] = self::get($k);
			}
			return $result;
		}
		if (!is_string($mixed)) {
			return null;
		}
		$uri = avalue(self::$zesk_ec2_map, $mixed);
		if ($uri === null) {
			throw new Exception_Key("No such setting $mixed");
		}
		$now = time();
		$expire = $now - self::$cache_expire_seconds;
		//
		// If I get a key at t=90, stored in self::$cached_time
		//
		// Our expiration time is, say 60 seconds
		// Now it's t=100
		// $expire would be 100-60 = 40
		// So comparing 40 to 90
		// if (40 < 90) cache is ok
		//
		// A little later, t=200
		//
		// $expire would be 200-60=140
		//
		// Comparing 240 to 90
		//
		// if (140 < 90); then cache is valid; else cache is invalid; fi
		//
		if (array_key_exists($mixed, self::$cached) && $expire < aevalue(self::$cached_times, $mixed, 0)) {
			return self::$cached[$mixed];
		}
		$result = self::$cached[$mixed] = self::fetch($uri);
		self::$cached_times[$mixed] = $now;
		return $result;
	}
	
	/**
	 *
	 * @param string $uri
	 *        	Field to retrieve
	 * @return string
	 */
	private static function fetch($uri) {
		$url = glue(self::$url, "/", "$uri/");
		$result = null;
		if (to_bool(ini_get("allow_url_fopen"))) {
			$result = file_get_contents($url);
		} else {
			$client = new Net_HTTP_Client($url);
			$result = $client->go();
			unset($client);
		}
		return $result;
	}
}

/*
 *
 * Known URLs extracted from ec2-metadata script
 *
http://169.254.169.254/latest/meta-data/ami-id
http://169.254.169.254/latest/meta-data/ami-launch-index
http://169.254.169.254/latest/meta-data/ami-manifest-path
http://169.254.169.254/latest/meta-data/ancestor-ami-ids
http://169.254.169.254/latest/meta-data/block-device-mapping/
http://169.254.169.254/latest/meta-data/block-device-mapping/$i
http://169.254.169.254/latest/meta-data/instance-id
http://169.254.169.254/latest/meta-data/instance-type
http://169.254.169.254/latest/meta-data/local-hostname
http://169.254.169.254/latest/meta-data/local-ipv4
http://169.254.169.254/latest/meta-data/kernel-id
http://169.254.169.254/latest/meta-data/placement/availability-zone
http://169.254.169.254/latest/meta-data/product-codes
http://169.254.169.254/latest/meta-data/public-hostname
http://169.254.169.254/latest/meta-data/public-ipv4
http://169.254.169.254/latest/meta-data/public-keys/
http://169.254.169.254/latest/meta-data/public-keys/$index/
http://169.254.169.254/latest/meta-data/public-keys/$index/$format
http://169.254.169.254/latest/meta-data/ramdisk-id
http://169.254.169.254/latest/meta-data/reservation-id
http://169.254.169.254/latest/meta-data/security-groups

And example outputs:

ami-id: ami-423bc82b
ami-launch-index: 0
ami-manifest-path: (unknown)
ancestor-ami-ids: not available
block-device-mapping: 
         ami: /dev/sda1
         ebs2: /dev/sdb
         root: /dev/sda1
instance-id: i-9ffea0fe
instance-type: t1.micro
local-hostname: domU-12-31-39-09-B6-0D.compute-1.internal
local-ipv4: 10.210.185.247
kernel-id: aki-407d9529
placement: us-east-1c
product-codes: not available
public-hostname: ec2-184-73-21-9.compute-1.amazonaws.com
public-ipv4: 184.73.21.9
public-keys: 
keyname:ruler-external
index:0
format:openssh-key
key:(begins from next line)
ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQCpZuGh/gX8eTQmaZ2TBHyxrRcBAVn6BtY74H0nTx+/l9lyW9iPjRjCSgTe3LzcmeRkyF7OBYiQHVVsxeSy0raRlMgSQvfTCxu8SN6u9ogVcvSak7+XgDwpT20PWyDuTnMswaAy6EkTasJQ+EzlfyVpImKa4BNbAHed61pYzlT7Cx2TdWY/tC31/VksSEzgh0sgsP7ptgBLGXb8CVLxbJ59Fh6GaytKc8dkhjY1a28z0zj4ZPZ2aB+DclyG+Ksb3zGEvwwzgHXmRmAfKaABdenlUxlZQLrlma65TGS9YLW3eUtuCweF2uXJCZaBGWs1GP/TpjKs57jVCwmhrVh1j+lR ruler-external
ramdisk-id: not available
reservation-id: r-03422a6c
security-groups: ruler-root-group
user-data: https://hades.marketruler.com/

 */
