<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Doctrine
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Doctrine\Command;

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;

use zesk\Command as BaseCommand;

class Doctrine extends BaseCommand
{
	protected array $shortcuts = ['doctrine'];

	protected array $option_types = ['*' => 'string'];

	public function run(): int
	{
		$entityManager = $this->application->entityManager();
		$argv = $this->argumentsRemaining();
		array_unshift($argv, self::class);
		$savedArgv = $_SERVER['argv'];
		$_SERVER['argv'] = $argv;
		ConsoleRunner::run(new SingleManagerProvider($entityManager));
		$_SERVER['argv'] = $savedArgv;
	}
}
