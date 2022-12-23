<?php
declare(strict_types=1);
/**
 * Define an interface to name/value pairs
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
interface Interface_Persist {
	/**
	 * Save to persistent storage.
	 *
	 * @return void
	 */
	public function store(): void;

	/**
	 * Load from persistent storage
	 *
	 * @return void
	 */
	public function load(): void;
}
