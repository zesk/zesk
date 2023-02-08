<?php declare(strict_types=1);

namespace zesk;

/**
 * Interface which represent a user of an application
 */
interface Interface_UserLike {
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
}
