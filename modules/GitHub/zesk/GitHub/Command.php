<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage GitHub
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\GitHub;

use Throwable;
use zesk\Command\SimpleCommand;
use zesk\Exception;
use zesk\Exception\FileNotFound;
use zesk\Exception\NotFoundException;
use zesk\File;

/**
 *
 * @author kent
 *
 */
class Command extends SimpleCommand {
	protected array $shortcuts = ['github'];

	/**
	 *
	 */
	public const OPTION_DESCRIPTION = 'description';

	/**
	 *
	 * @var array
	 */
	protected array $option_types = [
		'tag' => 'boolean',
		'description-file' => 'file',
		self::OPTION_DESCRIPTION => 'string',
		'commitish' => 'string',
	];

	/**
	 *
	 * @var array
	 */
	protected array $options = [
		'description' => 'Release of version {version}.',
		'commitish' => 'master',
	];

	/**
	 *
	 * @var integer
	 */
	public const EXIT_CODE_NO_DESCRIPTION = 1;

	/**
	 *
	 * @var integer
	 */
	public const EXIT_CODE_GITHUB_MODULE = 2;

	/**
	 *
	 * @var integer
	 */
	public const EXIT_CODE_TAG_FAILED = 3;

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Command::run()
	 */
	public function run(): int {
		if ($this->optionBool('tag')) {
			return $this->command_tag();
		}
		$this->usage('Need to specify --tag');
		return 0;
	}

	/**
	 *
	 * @return int
	 */
	public function command_tag(): int {
		$file = $this->option('description-file');

		try {
			$description = File::contents($file);
		} catch (FileNotFound) {
			$description = $this->option(self::OPTION_DESCRIPTION);
		}
		if (!$description) {
			$this->error('Need a non-blank description');
			return self::EXIT_CODE_NO_DESCRIPTION;
		}
		$description = map($description, $this->description_variables());

		try {
			/* @var $github Module */
			$github = $this->application->modules->object('GitHub');
			if ($github->generateTag('v' . $this->application->version(), $this->option('commitish'), $description)) {
				return 0;
			}
			return self::EXIT_CODE_TAG_FAILED;
		} catch (NotFoundException $not_found) {
			$this->error('Running {this_class} but GitHub module not loaded.', [
				'this_class' => get_class($this),
			]);
			return self::EXIT_CODE_GITHUB_MODULE;
		} catch (Throwable $e) {
			$this->error('Running {this_class} but unknown exception {class} {message}', [
				'this_class' => get_class($this),
			] + Exception::exceptionVariables($e));
			return self::EXIT_CODE_GITHUB_MODULE;
		}
	}

	public function description_variables(): array {
		return [
			'version' => $this->application->version(),
		];
	}
}
