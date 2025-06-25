<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\ApplicationHook;

use zesk\Application;
use zesk\FilterMethod;

interface Security
{
	/**
	 * @param Application $application
	 * @param string $message
	 * @param array $arguments
	 * @return void
	 */
	#[FilterMethod(handles: Application::HOOK_SECURITY)]
	public function securityHook(Application $application, string $message, array $arguments = []): void;
}
