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
class Exception_Permission extends Exception {
	function __construct(User $user, $action, Model $object = null, array $options = array(), $previous = null) {
		parent::__construct("User {user.name} has no permission action={action} class={class} type={type}", array(
			"action" => $action,
			"object" => $object,
			"class" => is_object($object) ? get_class($object) : "-",
			"type" => !is_object($object) ? gettype($object) : "",
			"options" => $options,
			"user" => $user,
			'user.name' => strval($user)
		));
	}
	function action() {
		return $this->arguments['action'];
	}
	function model() {
		return $this->arguments['object'];
	}
}
