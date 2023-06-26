<?php
/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
declare(strict_types=1);

namespace zesk;

/**
 * For an object which provides hook source paths to load PHP files.
 */
interface HookSource {
	/**
	 * @return array
	 */
	public function hookSources(): array;
}
