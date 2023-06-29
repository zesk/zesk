<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage interfaces
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Interface;

use zesk\Exception\ExitedException;

/**
 *
 */
interface Promptable
{
	/**
	 * @param string $message
	 * @param string $default
	 * @param array $completions
	 * @return string
	 * @throws ExitedException
	 */
	public function prompt(string $message, string $default = '', array $completions = []): string;
}
