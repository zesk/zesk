<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk\ORM;

/**
 *
 * @author kent
 *
 */
interface Interface_Session extends Interface_Settings {
	/**
	 *
	 * @param mixed $mixed
	 * @param array $options
	 * @param Application $application
	 */
	public function __construct(Application $application, mixed $mixed = null, array $options = []);

	/**
	 * Configure session connected to the Request
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
	 * @param Interface_Userlike $user The user identifier
	 * @param string $ip The ip address (optional)
	 * @return void
	 * @throws Exception_Authentication
	 */
	public function authenticate(Interface_Userlike $user, string $ip = ''): void;

	/**
	 * User currently authenticated?
	 *
	 * @return boolean
	 */
	public function authenticated(): bool;

	/**
	 * Relinquish the authentication of the current user. Throws Exception_Semantics if not authenticated.
	 *
	 * @return void
	 * @throws Exception_Semantics
	 */
	public function relinquish(): void;

	/**
	 * Retrieve user identifier
	 * @return int User ID
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Authentication
	 */
	public function userId(): int;

	/**
	 * Retrieve user
	 * @return Interface_Userlike User object
	 * @throws Exception_Authentication
	 */
	public function user(): Interface_Userlike;

	/**
	 * Delete the session
	 */
	public function delete(): void;
}
