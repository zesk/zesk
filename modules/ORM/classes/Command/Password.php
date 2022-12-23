<?php
declare(strict_types=1);

namespace zesk;

use zesk\ORM\User;

/**
 * Update user password in the system.
 *
 * If not supplied via the command-line, password is read from console.
 * @author kent
 * @category Management
 */
class Command_Password extends Command_Base {
	protected array $option_types = [
		'user' => 'string', 'password' => 'string', 'list' => 'boolean',
	];

	protected array $option_help = [
		'user' => 'The user to edit', 'password' => 'The new password',
		'list' => 'List the active users in the database',
	];

	public function _optionIterable(): void {
		$user = $this->application->ormFactory(User::class);
		$col = $user->column_login();
		$iterator = $user->querySelect()->addWhat($col)->order_by($col)->iterator(null, $col);
		$n = 0;
		foreach ($iterator as $login) {
			fprintf(STDOUT, "$login\n");
			++$n;
		}
		fprintf(STDERR, '# ' . $this->application->locale->plural_word('user', $n) . "\n");
	}

	public function run(): int {
		if ($this->optionBool('list')) {
			$this->_optionIterable();
			return 0;
		}
		$login = $this->option('user');
		if ($login === null) {
			fprintf(STDERR, "Need to supply a user name\n");
			return 1;
		}

		try {
			$foundUser = $this->application->ormFactory(User::class)->setLogin($login)->find();
		} catch (Exception_NotFound) {
			$this->error("User \"$login\" not found\n");
			return 1;
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
				return 3;
			}
		}
		$foundUser->setPassword($pass, true);
		if (!$foundUser->store()) {
			$id = $foundUser->id();
			fprintf(STDERR, "Unabled to save $user ($id) " . implode(', ', $user->store_error()) . "\n");
			return 2;
		}
		echo "Updated user password.\n";
		return 0;
	}
}
