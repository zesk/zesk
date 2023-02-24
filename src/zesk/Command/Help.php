<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Command
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Command;

use ReflectionClass;
use ReflectionException;
use zesk\Command;
use zesk\CommandLoader;
use zesk\Directory;
use zesk\DocComment;
use zesk\Types;

/**
 * This help.
 *
 * @category Documentation
 */
class Help extends SimpleCommand {
	protected array $shortcuts = ['help'];

	protected array $option_types = [
		'no-core' => 'boolean',
	];

	protected array $option_help = [
		'no-core' => 'Skip all Zesk core commands',
	];

	/**
	 *
	 * @var array
	 */
	private array $categories = [];

	/**
	 *
	 * @var array
	 */
	private array $aliases = [];

	public function run(): int {
		$loader = CommandLoader::factory()->setApplication($this->application);
		$commands = $loader->collectCommands();
		$this->collectHelp($commands);
		echo $this->application->themes->theme(Directory::path(__CLASS__, 'content'), [
			'categories' => $this->categories, 'aliases' => $this->aliases,
		]);
		return 0;
	}

	public function processCommandClass(string $class): bool {
		$this->verboseLog("Checking $class");

		try {
			$reflection_class = new ReflectionClass($class);
			if ($reflection_class->isAbstract()) {
				$this->verboseLog('{class} is abstract, skipping', [
					'class' => $class,
				]);
				return false;
			}
			/* @var $commandObject Command */
			$commandObject = $reflection_class->newInstanceArgs([$this->application]);
			assert($commandObject instanceof Command);
		} catch (ReflectionException) {
			$this->verboseLog('{class} can not be loaded, skipping', [
				'class' => $class,
			]);
			return false;
		}
		$command_file = $reflection_class->getFileName();
		$docCommentString = $reflection_class->getDocComment();
		$docComment = is_string($docCommentString) ? DocComment::instance($docCommentString)->variables() : [];
		if (array_key_exists('ignore', $docComment)) {
			return false;
		}
		$docCommentAliases = [];
		if (array_key_exists('shortcuts', $docComment)) {
			$docCommentAliases = Types::toList($docComment['shortcuts'], [], ' ');
		}
		$shortcuts = array_merge($commandObject->shortcuts(), $docCommentAliases);
		if (!count($shortcuts)) {
			return false;
		}
		$preferredAlias = array_shift($shortcuts);
		if (count($shortcuts)) {
			$docComment['shortcuts'] = $shortcuts;
		}
		$docComment['command'] = $preferredAlias;
		$docComment['command_file'] = $command_file;
		$category = $docComment['category'] ?? 'Miscellaneous';
		$this->categories[$category][$preferredAlias] = $docComment;
		return true;
	}

	public function collectHelp(array $classes): void {
		$this->aliases = [];
		$this->categories = [];
		foreach ($classes as $class) {
			$this->processCommandClass($class);
		}
		ksort($this->categories);
	}
}
