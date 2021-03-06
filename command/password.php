<?php
namespace zesk;

/**
 * Update user password in the system.
 *
 * If not supplied via the command-line, password is read from console.
 * @author kent
 * @category Management
 */
class Command_Password extends Command_Base {
	protected $option_types = array(
		"user" => "string",
		"password" => "string",
		"list" => "boolean",
	);

	protected $option_help = array(
		"user" => "The user to edit",
		"password" => "The new password",
		"list" => "List the active users in the database",
	);

	public function _option_list() {
		/* @var $user User */
		$user = $this->application->orm_factory('User');
		$col = $user->column_login();
		$iterator = $user->query_select()
			->what($col)
			->order_by($col)
			->iterator(null, $col);
		$n = 0;
		foreach ($iterator as $login) {
			fprintf(STDOUT, "$login\n");
			++$n;
		}
		fprintf(STDERR, "# " . $this->application->locale->plural_word("user", $n) . "\n");
		return true;
	}

	public function run() {
		if ($this->option_bool('list')) {
			return $this->_option_list();
		}
		$login = $this->option('user');
		if ($login === null) {
			fprintf(STDERR, "Need to supply a user name\n");
			exit(1);
		}
		$user = new User();
		$user->login($login);
		if (!$user->find()) {
			$this->error("User \"$login\" not found\n");
			exit(1);
		}
		$pass = $this->option('password');
		if ($pass === null) {
			echo "New Password: ";
			system('stty -echo');
			$pass = rtrim(fgets(STDIN));
			system('stty echo');
			echo "\n       Again: ";
			flush();
			system('stty -echo');
			$pass1 = rtrim(fgets(STDIN));
			system('stty echo');
			echo "\n";
			if ($pass !== $pass1) {
				$this->error("Password mismatch.\n");
				exit(3);
			}
		}
		$user->password($pass, true);
		if (!$user->store()) {
			$id = $user->id();
			fprintf(STDERR, "Unabled to save $user ($id) " . implode(", ", $user->store_error()) . "\n");
			exit(2);
		}
		echo "Updated user password.\n";
		return true;
	}
}
