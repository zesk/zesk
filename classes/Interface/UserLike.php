<?php declare(strict_types=1);

namespace zesk;

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
}
