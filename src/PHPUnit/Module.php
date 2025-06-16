<?php
declare(strict_types=1);
/**
 * PHPUnit
 *
 * @package zesk
 * @subpackage PHPUnit
 * @author kent
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\PHPUnit;

use zesk\Module as BaseModule;

class Module extends BaseModule
{
	public function initialize(): void
	{
		$this->setOptions([
			'urlLicense' => 'https://raw.githubusercontent.com/sebastianbergmann/phpunit/main/LICENSE',
			'name'       => 'PHP Unit tests and tools to work with Zesk and PHPUnit',
			'urlProject' => 'https://phpunit.de/',
			'licenses'   => [
				'BSD3',
			],
		]);
	}
}
