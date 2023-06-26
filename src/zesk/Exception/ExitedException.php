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
 * When a process or the user has sent an exited signal
 */
class ExitedException extends Exception {
}
