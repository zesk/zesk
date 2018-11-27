<?php

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
    public function __construct(Application $application, $mixed = null, array $options = array());

    /**
     * Configure session connected to the Request
     * @return self
     */
    public function initialize_session(Request $request);

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
     *
     * @param Response $response Optional response to set cookies
     */
    public function delete();
}
