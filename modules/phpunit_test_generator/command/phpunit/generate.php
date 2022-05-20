<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

use \SplFileInfo;

/**
 *
 * @alias testgen phpugen
 * @author kent
 */
class Command_PHPUnit_Generate extends Command_Iterator_File {
	/**
	 * Target directory where tests are created
	 *
	 * @var string
	 */
	protected $destination = null;

	/**
	 * (non-PHPdoc)
	 *
	 * @see Command_Base::initialize()
	 */
	public function initialize(): void {
		$this->option_types += [
			'destination' => 'directory',
		];
		parent::initialize();
	}

	/**
	 */
	protected function start(): void {
		$destination = $this->option('destination');
		if (Directory::is_absolute($destination)) {
			$this->destination = $destination;
		} else {
			$this->destination = $this->application->path($destination);
		}
		Directory::depend($this->destination);
	}

	/**
	 *
	 * @param SplFileInfo $file
	 */
	protected function process_file(\SplFileInfo $file): void {
	}

	/**
	 */
	protected function finish(): void {
	}
}
