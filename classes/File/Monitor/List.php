<?php declare(strict_types=1);
namespace zesk;

/**
 *
 * @author kent
 *
 */
class File_Monitor_List extends File_Monitor {
	protected array $files = [];

	/**
	 *
	 * @param array $files
	 */
	public function __construct(array $files) {
		$this->files = $files;
		parent::__construct();
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\File_Monitor::files()
	 */
	protected function files(): array {
		return $this->files;
	}
}
