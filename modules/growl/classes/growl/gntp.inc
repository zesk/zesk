<?php
/**
 * Growl_GNTP
 * 
 * Implements modern Growl protocol.
 * 
 * Based loosely on Net_Growl from PEAR
 * 
 * @author kent
 * @copyright (C) 2012 Market Acumen, Inc.
 */
class Growl_GNTP extends Growl {
	
	const Version = "1.0";
	
	const Message_Notify = "NOTIFY";
	
	const Message_Register = "REGISTER";
	
	const Message_Subscribe = "SUBSCRIBE";
	
	const Message_End = "END";
	
	protected $default_port = 23053;
	
	static $hash_algorithms = array(
		'md5', 
		'sha1', 
		'sha256', 
		'sha512'
	);
	
	static $encryption_algorithms = array(
		'3des', 
		'aes', 
		'des'
	);
	
	private $data = array();
	
	private $binaries = array();
	
	private $message = null;
	
	private $message_keys = null;
	
	private $iv_val = null;
	
	function validate_algorithms() {
		$hash_algorithm = $this->hash_algorithm();
		$encryption_algorithm = $this->encryption_algorithm();
		$debug_data = array(
			"hash" => $hash_algorithm, 
			"encrypt" => $encryption_algorithm
		);
		if ($hash_algorithm !== 'none') {
			if (!in_array($hash_algorithm, hash_algos())) {
				throw new Exception_Unsupported(__('Password hash algorithm not supported in mcrypt: {hash}', $debug_data));
			}
			if (!in_array($hash_algorithm, self::$hash_algorithms)) {
				throw new Exception_Unsupported(__('Unknown password hash algorithm: {hash}', $debug_data));
			}
		}
		if ($encryption_algorithm !== 'none') {
			if (!function_exists("mcrypt_module_open")) {
				throw new Exception_Unsupported(__('Need mcrypt to supprt encryption algorithm: {encrypt}', $debug_data));
			}
			if (!in_array($encryption_algorithm, self::$encryption_algorithms)) {
				throw new Exception_Unsupported(__('Unknown encryption algorithm: {encrypt}', $debug_data));
			}
			switch ($encryption_algorithm) {
				case '3des':
				case "aes":
					if ($hash_algorithm !== "sha256" && $hash_algorithm !== "sha512") {
						throw new Exception_Unsupported(__('Encryption and hash algorithms incompatible: {hash}/{encrypt}', $debug_data));
					}
					break;
			}
		}
	}
	
	private function message_start() {
		$this->validate_algorithms();
		$this->data = array();
		$this->binaries = array();
		$this->message_header("X-Sender", "Zesk/Growl " . self::Version . " PHP " . phpversion());
		$this->message_header("Origin-Machine-Name", php_uname('n'));
		$this->message_header("Origin-Platform-Name", php_uname('s'));
		$this->message_header("Origin-Platform-Version", php_uname('r'));
		$this->message_header("Origin-Software-Name", zesk::application_class());
		$this->message_header("Origin-Software-Version", zesk::version());
		return $this;
	}
	
	private function message_header($name, $value) {
		if (empty($value)) {
			return $this;
		}
		$this->data[] = "$name: " . utf8_encode($value);
		return $this;
	}
	
	private function message_icon($name, $icon_url = null, $icon_file = null) {
		if ($icon_url) {
			$this->message_header($name, $icon_url);
		} else if ($icon_file && file_exists($icon_file)) {
			$hash = md5_file($icon_file);
			$this->message_header($name, "x-growl-resource://$hash");
			$this->binaries[$hash] = file_get_contents($icon_file);
		}
		return $this;
	}
	
	private function message_break() {
		$this->data[] = "";
		return $this;
	}
	
	public function register() {
		/*
		Application-Name: <string>
		Required - The name of the application that is registering
		
		Application-Icon: <url> | <uniqueid>
		Optional - The icon of the application
		
		Notifications-Count: <int>
		Required - The number of notifications being registered
		
		For each notification being registered:
		
		Notification-Name: <string>
		Required - The name (type) of the notification being registered
		
		Notification-Display-Name: <string>
		Optional - The name of the notification that is displayed to the user (defaults to the same value as Notification-Name)
		
		Notification-Enabled: <boolean>
		Optional - Indicates if the notification should be enabled by default (defaults to False)
		
		Notification-Icon: <url> | <uniqueid>

		 */
		$this->message_start();
		
		$this->message_header("Application-Name", $this->name);
		$this->message_icon("Application-Icon", $this->option('icon_url'), $this->option('icon_file'));
		$this->message_header("Notifications-Count", count($this->notifications));
		
		foreach ($this->notifications as $index => $notification) {
			$this->message_break();
			$name = $display = $icon_url = $icon_file = $enabled = null;
			extract($notification, EXTR_IF_EXISTS);
			$this->message_header("Notification-Name", $name);
			$this->message_header("Notification-Display-Name", $display);
			$this->message_icon("Notification-Icon", $icon_url, $icon_file);
			$this->message_header("Notification-Enabled", ($enabled ? "true" : "false"));
		}
		
		return $this->message_send(self::Message_Register);
	}
	
	private function message_send($message_type) {
		$this->message = null;
		$this->message_keys = $this->message_hash($this->option('hash_algorithm'), avalue($this->parts, 'password'));
		$this->message_build($message_type, $this->data);
		$this->data = array();
		foreach ($this->binaries as $id => $binary) {
			$data = array(
				"Identifier: " . utf8_encode($id), 
				"Length: " . strlen($data), 
				"", 
				$binary
			);
			$this->message_build($message_type, $data);
		}
		$this->message .= "\r\n\r\n";
		
		$host = $port = null;
		extract($this->parts, EXTR_IF_EXISTS);
		$sock = @fsockopen($host, $port);
		if (!$sock) {
			throw new Exception_Connect("$host:$port", "Unable to connect t GNTP host {host}:{port}", array(
				"host" => $host, 
				"port" => $port
			));
		}
		fwrite($sock, $this->message);
		
		$response = fread($sock, 10248);
		echo $response;
		fclose($sock);
		
		return $this;
	}
	
	private function message_build($message_type, array $data) {
		/*  
		 * GNTP/<version> <messagetype> <encryptionAlgorithmID>[:<ivValue>][ <keyHashAlgorithmID>:<keyHash>.<salt>]
		 * 
		 * where GNTP is the name of the protocol and:
		 * 
		 * <version> The GNTP version number. Currently, the only supported version is '1.0'.
		 * 
		 * <messagetype> Identifies the type of message. See 'Requests' and 'Responses' sections below for supported values
		 * 
		 * <encryptionAlgorithmID> Identifies the type of encryption used on the message. See 'Encryption' section below for supported values
		 * 
		 * <ivValue> If the message is encrypted, this is the hex-encoded initialization value
		 * 
		 * <keyHashAlgorithmID> Identifies the type of hashing algorithm used. See 'Encryption' section below for supported values
		 * 
		 * <keyHash> The hex-encoded hash of the key
		 * 
		 * <salt> The hex-encoded salt value
		 * 
		 * NOTE: <keyHashAlgorithm>, <keyHash>, and <salt> are not normally required for requests that originate on the local machine.
		 * 
		 * The GNTP information line is followed by a <CRLF> and then any message headers. Note that, just like with other MIME-like protocols, trailing whitespace is allowed and should safely be ignored.
		 */
		list($hash, $key) = $this->message_keys;
		list($crypt, $message) = $this->message_encrypt($key, implode("\r\n", $data));
		if ($this->message === null) {
			$this->message = strtoupper("GNTP/" . self::Version . " $message_type $crypt $hash\r\n");
		}
		$this->message .= $message;
	}
	
	public function notify($name, $title, $description, $priority = 0, array $options = array()) {
		/*
		 Application-Name: <string>
		Required - The name of the application that sending the notification (must match a previously registered application)
		
		Notification-Name: <string>
		Required - The name (type) of the notification (must match a previously registered notification name registered by the application specified in Application-Name)
		
		Notification-ID: <string>
		Optional - A unique ID for the notification. If used, this should be unique for every request, even if the notification is replacing a current notification (see Notification-Coalescing-ID)
		
		Notification-Title: <string>
		Required - The notification's title
		
		Notification-Text: <string>
		Optional - The notification's text. (defaults to "")
		
		Notification-Sticky: <boolean>
		Optional - Indicates if the notification should remain displayed until dismissed by the user. (default to False)
		
		Notification-Priority: <int>
		Optional - A higher number indicates a higher priority. This is a display hint for the receiver which may be ignored. (valid values are between -2 and 2, defaults to 0)
		
		Notification-Icon: <url> | <uniqueid>
		Optional - The icon to display with the notification.
		
		Notification-Coalescing-ID: <string>
		Optional - If present, should contain the value of the Notification-ID header of a previously-sent notification. This serves as a hint to the notification system that this notification should replace/update the matching previous notification. The notification system may ignore this hint.
		
		Growl for Windows v2.0+ and Growl v1.3+ require Notification-Coalescing-ID to be the same on both the original and updated notifcation, ignoring the value of Notification-ID.
		
		Notification-Callback-Context: <string>
		Optional - Any data (will be passed back in the callback unmodified)
		
		Notification-Callback-Context-Type: <string>
		Optional, but Required if 'Notification-Callback-Context' is passed - The type of data being passed in Notification-Callback-Context (will be passed back in the callback unmodified). This does not need to be of any pre-defined type, it is only a convenience to the sending application.
		
		Notification-Callback-Target: <string>
		Optional - An alternate target for callbacks from this notification. If passed, the standard behavior of performing the callback over the original socket will be ignored and the callback data will be passed to this target
		instead. See
		*/
		$this->message_header("Application-Name", $this->name, false);
		$this->message_header("Notification-Name", $name);
		$this->message_header("Notification-Title", $title);
		$this->message_header("Notification-Text", $description);
		$this->message_header("Notification-Priority", $priority);
		$this->message_header("Notification-Icon", avalue($options, 'icon_url'), avalue($options, 'icon_file'));
		if (array_key_exists("sticky", $options)) {
			$this->message_header("Notification-Sticky", to_bool($options['sticky']) ? "true" : "false");
		}
		$id = avalue($options, 'id');
		if ($id) {
			$this->message_header("Notification-ID", $id);
		}
		return $this->message_send(self::Message_Notify);
	}
	
	private function message_hash($hash_algorithm, $password) {
		if (empty($hash_algorithm) || $hash_algorithm === 'none') {
			return array(
				'NONE', 
				''
			);
		}
		$salt_bytes = pack("H*", $salt_hex = dechex(mt_rand(0x10000000, mt_getrandmax())));
		$key_source = pack("H*", bin2hex($password)) . $salt_bytes;
		$key_hash = hash($hash_algorithm, $key = hash($hash_algorithm, $key_source, true));
		
		return array(
			"$hash_algorithm:$key_hash.$salt_hex", 
			$key
		);
	}
	
	public function hash_algorithm($set = null) {
		if ($set !== null) {
			return $this->set_option("hash_algorithm", $set);
		}
		return strtolower($this->option("hash_algorithm", "none"));
	}
	
	public function encryption_algorithm($set = null) {
		if ($set !== null) {
			return $this->set_option("encryption_algorithm", $set);
		}
		return strtolower($this->option("encryption_algorithm", "none"));
	}
	
	private function message_encrypt($key, $text) {
		$crypt_algorithm = $this->encryption_algorithm();
		if (empty($crypt_algorithm) || $crypt_algorithm === "none") {
			return array(
				"none", 
				$text
			);
		}
		$key_size = null;
		switch ($crypt_algorithm) {
			case 'aes':
				$cipher = MCRYPT_RIJNDAEL_128;
				$key_size = 24;
				break;
			case 'des':
				$cipher = MCRYPT_DES;
				break;
			case '3des':
				$cipher = MCRYPT_3DES;
				break;
		}
		
		$crypt = mcrypt_module_open($cipher, '', MCRYPT_MODE_CBC, '');
		$iv_size = mcrypt_enc_get_iv_size($crypt);
		$block_size = mcrypt_enc_get_block_size($crypt);
		if ($key_size === null) {
			$key_size = mcrypt_enc_get_key_size($crypt);
		}
		if ($this->iv_val === null) {
			$this->iv_val = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		}
		$iv_hex = bin2hex($this->iv_val);
		$key = substr($key, 0, $key_size);
		$init = mcrypt_generic_init($crypt, $key, $this->iv_val);
		if ($init < 0 || $init === false) {
			$length = strlen($text);
			$pad = $block_size - ($length % $block_size);
			$plainText = str_pad($plainText, $length + $pad, chr($pad));
			$cipher = mcrypt_generic($crypt, $text);
			mcrypt_generic_deinit($crypt);
			mcrypt_module_close($crypt);
		} else {
			$cipher = $plainText;
		}
		return array(
			"$crypt_algorithm:$iv_hex", 
			$cipher
		);
	}
}
