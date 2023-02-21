<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Exception
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Exception;

use Throwable;
use zesk\Exception;
use zesk\Interface\Userlike;
use zesk\ORM\ORMBase;

/**
 *
 * @author kent
 *
 */
class PermissionDenied extends Exception {
	public function __construct(Userlike $user, $action, ORMBase $object = null, array $options = [], Throwable
	$previous =
	null) {
		parent::__construct('User {user.name} has no permission action={action} class={class} type={type}', [
			'action' => $action,
			'object' => $object,
			'class' => is_object($object) ? $object::class : '-',
			'type' => !is_object($object) ? gettype($object) : '',
			'options' => $options,
			'user' => $user,
			'user.name' => strval($user),
		], 0, $previous);
	}

	public function action() {
		return $this->arguments['action'];
	}

	public function model() {
		return $this->arguments['object'];
	}
}
