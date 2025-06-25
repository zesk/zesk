<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage Traits
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Traits;

use zesk\Application;

trait Factory
{
	/**
	 *
	 * @param Application $application
	 * @param array $options
	 * @return self
	 */
	public static function factory(Application $application, array $options): self
	{
		return new self($application, $options);
	}
}
