<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Login
 * @author Kent Davidson
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Login;

use zesk\Module as BaseModule;
use zesk\ORM\User;

class Module extends BaseModule
{
	public function modelClasses(): array
	{
		return array_merge(parent::modelClasses(), [User::class]);
	}
}
