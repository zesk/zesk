<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Cache/APC.php $
* @package zesk
* @subpackage default
* @author Kent Davidson <kent@marketacumen.com>
* @copyright Copyright &copy; 2006, Market Acumen, Inc.
*/

namespace zesk;

if (!function_exists('apc_exists')) {
	function apc_exists($keys) {
		$result = false;
		apc_fetch($keys, $result);
		return $result;
	}
}

/**
 * APC cache
 *
 * Local memory on a system
 *
 * @package zesk
 * @subpackage system
 * @deprecated 2017-06
 */
class Cache_APC extends Cache {

	protected $_ttl = 0;

	protected function static_installed() {
		return function_exists('apc_fetch');
	}

	/**
	 * Check at flush time whether the "disabled" file exists.
	 * We check once on startup, and once at exit
	 *
	 * @var Boolean
	 */
	protected static function static_initialize() {
		if (!self::$disabled) {
			self::$disabled = self::_disabled_entry_exists();
		}
	}

	protected static function static_exists($name) {
		if (self::$disabled) {
			return false;
		}
		return apc_exists($name);
	}

	protected function fetch() {
		if (self::$disabled) {
			return null;
		}
		$success = false;
		$data = apc_fetch($this->_name, $success);
		if ($success) {
			return $data;
		}
		return null;
	}

	protected function store($data) {
		if (self::$disabled) {
			return false;
		}
		return apc_store($this->_name, $data);
	}

	protected static function static_exit() {
		if (!self::$disabled) {
			self::$disabled = self::_disabled_flag_exists();
		}
	}

	public function exists() {
		if (self::$disabled) {
			return false;
		}
		return apc_exists($this->_name);
	}

	public function expire_after($n_seconds) {
		$this->_ttl = $n_seconds;
		return $this;
	}

	/**
	 * Delete the cache file
	 */
	public function delete() {
		apc_delete($this->_name);
	}

	private static function static_check_disabled() {
		return apc_fetch(__CLASS__ . "::disabled");
	}
}

