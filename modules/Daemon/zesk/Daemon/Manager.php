<?php
/**
 * @package zesk
 * @subpackage Daemon
 * @author kent
 * @copyright Copyright &copy; 2024, Market Acumen, Inc.
 */

declare(strict_types=1);

namespace zesk\Daemon;

use zesk\Hookable;

abstract class Manager extends Hookable
{
	abstract public function minimumProcessCount(): int;
}
