<?php declare(strict_types=1);
/**
 *
 */
namespace Server\classes\Server;

use zesk\Application;
use zesk\Model;
use zesk\path;

/**
 *
 * @author kent
 *
 */
class Server_User extends Model {
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

	public function __construct(Application $application, $options = null) {
		parent::__construct($application, $options);
		$this->inheritConfiguration();
		$this->user = $this->option('user');
		$this->group = $this->option('group');
		$this->home = $this->option('home');
	}
}
