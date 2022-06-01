<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

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
	public function __construct(Application $application, $mixed = null, array $options = []);

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
	 * @param User $user The user identifier
	 * @param string $ip The ip address (optional)
	 * @return void
	 * @throws Exception_Authentication
	 */
	public function authenticate(User $user, string $ip = ''): void;

	/**
	 * User currently authenticated?
	 *
	 * @return boolean
	 */
	public function authenticated(): bool;

	/**
	 * Unauthorize current user
	 * @return void
	 */
	public function deauthenticate(): void;

	/**
	 * Retrieve user identifier
	 * @return int User ID
	 * @throws Exception_NotFound
	 */
	public function userId(): int;

	/**
	 * Retrieve user
	 * @return User User object
	 */
	public function user(): User;

	/**
	 * Delete the session
	 */
	public function delete(): void;
}
