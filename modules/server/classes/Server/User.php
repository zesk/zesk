<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Server_User extends Hookable {
	/**
	 * User name
	 * @var string
	 */
	public $name = null;
	
	/**
	 * User group
	 * @var string
	 */
	public $group = null;
	
	/**
	 * User home directory
	 * @var path
	 */
	public $home = null;
	
	function __construct($options=null) {
		parent::__construct($options);
		$this->inherit_global_options();
		$this->user = $this->option('user');
		$this->group = $this->option('group');
		$this->home = $this->option('home');
	}
}
