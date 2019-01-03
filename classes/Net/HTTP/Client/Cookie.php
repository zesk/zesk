<?php
/**
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Net_HTTP_Client_Cookie {
	/**
	 *
	 * @var string
	 */
	public $name;

	/**
	 *
	 * @var string
	 */
	public $value;

	/**
	 *
	 * @var domain
	 */
	public $domain;

	/**
	 *
	 * @var string
	 */
	public $path;

	/**
	 *
	 * @var string
	 */
	public $expires;

	/**
	 *
	 * @var boolean
	 */
	public $secure;

	public function __construct($name, $value, $domain, $path, $expires = false, $secure = false) {
		$this->name = $name;
		$this->value = $value;
		$this->domain = strtolower($domain);
		$this->path = $path;

		$this->setExpires($expires);

		$this->secure = $secure ? true : false;
	}

	public function name() {
		return $this->name;
	}

	public function value() {
		return $this->value;
	}

	public function secure() {
		return $this->secure;
	}

	public function setExpires($expires) {
		if ($expires instanceof Timestamp) {
			$this->expires = $expires->unix_timestamp();
		} else {
			$this->expires = $expires;
		}
	}

	public function matches($domain, $path) {
		return (strcasecmp($domain, $this->domain) === 0) && (strcasecmp($path, $this->path) === 0);
	}

	public function update($value, $expires = null) {
		$this->value = $value;
		if ($expires) {
			$this->setExpires($expires);
		}
	}

	public function string() {
		return $this->__toString();
	}

	public function __toString() {
		return $this->name . "=" . urlencode($this->value);
	}
}
