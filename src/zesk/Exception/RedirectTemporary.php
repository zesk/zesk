<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Exception
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Exception;

use zesk\HTTP;

/**
 * @author kent
 */
class RedirectTemporary extends Redirect
{
	public function __construct($url, $message = null, array $arguments = [])
	{
		parent::__construct($url, $message, [
			self::RESPONSE_STATUS_CODE => HTTP::STATUS_TEMPORARY_REDIRECT,
		] + $arguments);
	}
}
