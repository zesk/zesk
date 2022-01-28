<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage interfaces
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
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
