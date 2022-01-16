<?php declare(strict_types=1);
namespace zesk;

/**
 * Monitors current include files for changes
 *
 * @author kent
 * @see File_Monitor
 */
class File_Monitor_Includes extends File_Monitor {
	/**
	 * Retrieve the list of included files, currently.
	 *
	 * Will grow, should never shrink.
	 *
	 * {@inheritDoc}
	 * @see \zesk\File_Monitor::files()
	 */
	protected function files(): array {
		return get_included_files();
	}
}
