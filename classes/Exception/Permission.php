<?php declare(strict_types=1);

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
	public function __construct(User $user, $action, Model $object = null, array $options = [], $previous = null) {
		parent::__construct('User {user.name} has no permission action={action} class={class} type={type}', [
			'action' => $action,
			'object' => $object,
			'class' => is_object($object) ? get_class($object) : '-',
			'type' => !is_object($object) ? gettype($object) : '',
			'options' => $options,
			'user' => $user,
			'user.name' => strval($user),
		]);
	}

	public function action() {
		return $this->arguments['action'];
	}

	public function model() {
		return $this->arguments['object'];
	}
}
