<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\Interface;

use zesk\Exception\PermissionDenied;
use zesk\Model;

/**
 * Interface which represent a user of an application
 */
interface Userlike {
	/**
	 * Returns public authentication data when logged in.
	 *
	 * @return array
	 */
	public function authenticationData(): array;

	/**
	 * Users have IDs which are unique, comparable, and
	 * @return int|string|array
	 */
	public function id(): int|string|array;

	/**
	 * @param string|array $actions
	 * @param null|Model $context
	 * @param array $options
	 * @return bool
	 */
	public function can(string|array $actions, Model $context = null, array $options = []): bool;

	/**
	 * @param string|array $actions
	 * @param null|Model $context
	 * @param array $options
	 * @return void
	 * @throws PermissionDenied
	 */
	public function must(string|array $actions, Model $context = null, array $options = []): void;
}
