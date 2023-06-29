<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2023 Market Acumen, Inc.
 * @author kent
 * @category Management
 * @package zesk
 * @subpackage logger
 */
namespace zesk\Logger;

interface Handler
{
	/**
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function log(string $message, array $context = []): void;
}
