<?php
namespace zesk;

class Server_Feature_Users extends Server_Feature {
	public $code = "users";

	protected $commands = array(
		'crontab',
	);

	protected $settings = array(
		'users' => array(
			'type' => 'user list',
			'required' => false,
			'default' => array(),
		),
		'home_parent_path' => array(
			'type' => 'path',
			'required' => false,
		),
	);

	private $require_users = array();

	private $require_groups = array();

	public function configure() {
		$users = $this->config->user_list("users");
		foreach ($users as $user) {
			$this->configure_user($user);
		}
	}

	public function require_user($user) {
		$this->require_users[] = $user;
	}

	public function require_group($group) {
		$this->require_groups[] = $group;
	}

	private function configure_user(array $object) {
		$user = $group = $home = $options = null;
		extract($object, EXTR_IF_EXISTS);
		if ($group === null) {
			$group = $user;
		}
		if ($home === null) {
			$home = path($this->config->variable('home_parent_path', 'path'), $user);
		}
		if (!$this->platform->group_exists($group)) {
			$this->platform->group_create($group);
		}
		if (!$this->platform->user_exists($user)) {
			$this->platform->user_create($user, $group, $home, $options);
		}
		if (!$this->platform->user_exists($user)) {
			$this->platform->user_create($user, $group, $home, $options);
		}
		$user = $this->platform->user($user);
	}
}
