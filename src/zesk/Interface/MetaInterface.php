<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Interface
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Interface;

interface MetaInterface {
	/**
	 * Getter for named meta data
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function meta(string $name): mixed;

	/**
	 * Set meta data by name
	 *
	 * This method should have immediate effect (in the database, on disk, etc.)
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return $this
	 */
	public function setMeta(string $name, mixed $value): self;

	/**
	 * Delete meta data by name
	 *
	 * This method should have immediate effect (in the database, on disk, etc.)
	 *
	 * @param array|string $name of datum to delete
	 * @return $this
	 */
	public function deleteMeta(array|string $name): self;

	/**
	 * Remove all meta data
	 *
	 * This method should have immediate effect (in the database, on disk, etc.)
	 *
	 * @return $this
	 */
	public function clearMeta(): self;
}
