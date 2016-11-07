<?php
/**
 *
 */
zesk()->deprecated();
/**
 * @deprecated 2016-09
 * @author kent
 *
 */
interface Interface_Session extends Interface_Settings {
	/**
	 * Singleton interface to retrieve current session
	 * @return Session
	 */
	public static function instance($create = true);

	/**
	 * Retrieve a unique value for this session
	 * @return mixed The unique ID of this session
	*/
	public function id();

	/**
	 * Authenticate a user in the system as being tied to this session. Optionally give the IP address
	 *
	 * @param mixed $id The user identifier
	 * @param integer $ip The ip address
	 * @return void
	*/
	public function authenticate($mixed, $ip = false);

	/**
	 * User currently authenticated?
	 *
	 * @return boolean
	*/
	public function authenticated();

	/**
	 * Unauthorize current user
	*/
	public function deauthenticate();

	/**
	 * Retrieve user identifier
	 * @return mixed User identifier, or null if not set
	*/
	public function user_id();

	/**
	 * Retrieve user
	 * @return User User object
	*/
	public function user();

	/**
	 * Delete any reference to this session, including cookies
	*/
	public function delete();
}

