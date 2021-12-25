<?php declare(strict_types=1);
namespace zesk;

class Server_Feature_Users extends Server_Feature {
	public $code = "users";

	protected $commands = [
		'crontab',
	];

	protected $settings = [
		'users' => [
			'type' => 'user list',
			'required' => false,
			'default' => [],
		],
		'home_parent_path' => [
			'type' => 'path',
			'required' => false,
		],
	];

	private $require_users = [];

	private $require_groups = [];

	public function configure(): void {
		$users = $this->config->user_list("users");
		foreach ($users as $user) {
			$this->configure_user($user);
		}
	}

	public function require_user($user): void {
		$this->require_users[] = $user;
	}

	public function require_group($group): void {
		$this->require_groups[] = $group;
	}

	private function configure_user(array $object): void {
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
