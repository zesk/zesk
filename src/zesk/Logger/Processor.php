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

interface Processor
{
	/**
	 * @param array $context
	 * @return array
	 */
	public function process(array $context);
}
