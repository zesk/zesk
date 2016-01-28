<?php
/**
 * Generate IP Ban include script to test application-level
 *
 * No dependencies on anything
 */
echo "<?php\n";
?>
class ipban_check {
	static function ip() {
		if (array_key_exists('ipban_test', $_REQUEST)) {
			return $_REQUEST['ipban_test'];
		}
		foreach (array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR'
		) as $k) {
			if (!array_key_exists($k, $_SERVER)) {
				continue;
			}
			return $_SERVER[$k];
		}
		return null;
	}

	static function banned($ip) {
		$ipban_fs = '<?php echo $this->fs_path; ?>';
		if (!is_dir($ipban_fs)) {
			return false;
		}
		$ip = explode(".", $ip);
		$ff[] = implode('/', $ip);
		array_pop($ip);
		$ff[] = implode('/', $ip) . '/*';
		array_pop($ip);
		$ff[] = implode('/', $ip) . '/*';
		$path = rtrim($ipban_fs, '/') . '/';
		foreach ($ff as $f) {
			if (file_exists($path . $f)) {
				return true;
			}
		}
		return false;
	}

	static function index() {
		$ip = self::ip();
		if (!$ip) {
			return;
		}
		if (self::banned($ip)) {
			header('HTTP/1.0 403 Banned');
			echo "You have been banned from using this service. ($ip)";
			exit();
		}
	}
}

ipban_check::index();
