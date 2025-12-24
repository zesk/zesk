<?php
/**
 * @package zesk
 * @subpackage Interfaces
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

declare(strict_types=1);

namespace zesk\Interface;

use zesk\Exception;

interface Initializer
{
	/** @throws Exception */
	public function initialize(): void;
}
