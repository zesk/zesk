<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Exception
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Exception;

use zesk\Exception;

/**
 * When a lock can not be obtained.
 *
 * @author kent
 */
class LockedException extends Exception
{
}
