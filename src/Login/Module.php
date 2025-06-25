<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage Login
 * @author     Kent Davidson
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Login;

use zesk\Doctrine\User;
use zesk\Module as BaseModule;

class Module extends BaseModule
{
	public function modelClasses(): array
	{
		return array_merge(parent::modelClasses(), [User::class]);
	}
}
