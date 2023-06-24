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
 * Throw when the user does not authenticate, authentication fails, or access issues
 *
 * @author kent
 */
class AuthenticationException extends Exception {
}
