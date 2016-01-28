<?php
/**
 * Growl client 
 * 
 * Rewritten to support Zesk coding standards
 * 
 * Thanks to Tyler Hall at https://github.com/tylerhall/php-growl/ for base code
 * 
 * @author Tyler Hall
 * @author Kent Davidson
 */
abstract class Growl extends Options {

	const Priority_Low = -2;

	const Priority_Moderate = -1;

	const Priority_Normal = 0;

	const Priority_High = 1;

	const Priority_Emergency = 2;

	protected $url = null;

	protected $name = null;

	protected $parts = array();

	protected $notifications = array();

	protected $default_port = -1;
	
	public static function factory($url, array $options = array()) {
		static $classes = array(
			"gntp" => "Growl_GNTP", 
			"udp" => "Growl_UDP"
		);
		$class = avalue($classes, strtolower(avalue($options, "protocol", "gntp")), "Growl_GNTP");
		return zesk::factory($class, $url, $options);
	}

	/**
	 * Create a Growl object associated with a server
	 * @param string $url A Growl URL in the form growl://:password@host:port/
	 * @param string $app_name This application's name
	 */
	function __construct($url, array $options = array()) {
		parent::__construct($options);
		$this->inherit_global_options();
		
		$this->parts = url::parse($url);
		if ($this->parts['scheme'] !== 'growl') {
			throw new Exception_Semantics("Need a growl:// URL ($url)");
		}
		$app_name = avalue($options, 'name', "Zesk Growl");
		$this->name = $app_name;
		// Set up defaults
		$this->parts['port'] = avalue($this->parts, 'port', $this->default_port);
		$this->notifications = array();
	}

	public function notification($name = null, $enabled = true) {
		if ($name === null) {
			return $this->notifications;
		}
		$this->notifications[] = array(
			'name' => $name, 
			'enabled' => $enabled
		);
		return $this;
	}

	abstract public function register();

	abstract public function notify($name, $title, $message, $priority = 0, array $options = array());
}
