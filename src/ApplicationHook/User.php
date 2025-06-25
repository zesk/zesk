<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\ApplicationHook;

use zesk\Application;
use zesk\FilterMethod;

interface User
{
	/**
	 * @param Application $application
	 * @param User|null $user
	 * @return User|null
	 */
	#[FilterMethod(handles: Application::HOOK_USER, argumentTypes: [
		Application::class,
		[
			User::class,
			'null',
		],
	])]
	public function userFilter(Application $application, null|User $user = null): User|null;
}
