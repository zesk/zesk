<?php
declare(strict_types=1);
/**
 * @package    zesk-modules
 * @subpackage diff
 * @author     kent
 * @copyright  &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Diff;

use zesk\Module as BaseModule;

/**
 *
 * @author kent
 *
 */
class Module extends BaseModule
{
	/*
	 * This port is based on the etc/diff.c.txt file
	 */
	public function initialize(): void
	{
		$this->setOptions([
			'name'        => 'Diff',
			'description' => 'Diff tools',
			'urlLicense'  => './etc/diff.c.txt',
			'licenses'    => [
				'MIT',
			],
		]);
	}
}
