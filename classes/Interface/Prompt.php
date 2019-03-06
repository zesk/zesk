<?php
/**
 * @package zesk
 * @subpackage interfaces
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk;

/**
 * @author kent
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
interface Interface_Prompt {
	public function prompt($message, $default = null, array $completions = null);
}
