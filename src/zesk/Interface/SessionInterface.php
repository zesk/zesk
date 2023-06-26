<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Interface;

use zesk\Application;
use zesk\Exception\AuthenticationException;
use zesk\Exception\SemanticsException;
use zesk\Request;

/**
 *
 * @author kent
 *
 */
interface SessionInterface extends SettingsInterface {
	/**
	 * Configure session connected to the Request
	 * @param Request $request
	 * @return self
	 */
	public function initializeSession(Request $request): self;

	/**
	 * Retrieve a unique value for this session
	 * @return int|string|array The unique ID of this session
	 */
	public function id(): int|string|array;

	/**
	 * Authenticate a user in the system as being tied to this session. Optionally give the IP address
	 *
	 * @param Userlike $user
	 * @param Request $request
	 * @return void
	 *@throws AuthenticationException
	 */
	public function authenticate(Userlike $user, Request $request): void;

	/**
	 * User currently authenticated?
	 *
	 * @return boolean
	 */
	public function isAuthenticated(): bool;

	/**
	 * Relinquish the authentication of the current user. Throws Semantics if not authenticated.
	 *
	 * @return void
	 * @throws SemanticsException
	 */
	public function relinquish(): void;

	/**
	 * Retrieve user identifier
	 * @return int User ID
	 * @throws AuthenticationException
	 */
	public function userId(): int;

	/**
	 * Retrieve user
	 * @return Userlike User object
	 * @throws AuthenticationException
	 */
	public function user(): Userlike;

	/**
	 * Delete the session
	 */
	public function delete(): void;
}
