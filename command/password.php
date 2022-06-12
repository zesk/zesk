<?php declare(strict_types=1);
namespace zesk;

/**
 * Update user password in the system.
 *
 * If not supplied via the command-line, password is read from console.
 * @author kent
 * @category Management
 */
class Command_Password extends Command_Base {
	protected array $option_types = [
		'user' => 'string',
		'password' => 'string',
		'list' => 'boolean',
	];

	protected array $option_help = [
		'user' => 'The user to edit',
		'password' => 'The new password',
		'list' => 'List the active users in the database',
	];

	public function _optionIterable() {
		/* @var $user User */
		$user = $this->application->ormFactory('User');
		$col = $user->column_login();
		$iterator = $user->query_select()
			->addWhat($col)
			->order_by($col)
			->iterator(null, $col);
		$n = 0;
		foreach ($iterator as $login) {
			fprintf(STDOUT, "$login\n");
			++$n;
		}
		fprintf(STDERR, '# ' . $this->application->locale->plural_word('user', $n) . "\n");
		return true;
	}

	public function run() {
		if ($this->optionBool('list')) {
			return $this->_optionIterable();
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
			echo 'New Password: ';
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
			fprintf(STDERR, "Unabled to save $user ($id) " . implode(', ', $user->store_error()) . "\n");
			exit(2);
		}
		echo "Updated user password.\n";
		return true;
	}
}
