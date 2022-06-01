<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage interfaces
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

interface Interface_Prompt {
	public function prompt(string $message, string $default = null, array $completions = null): string;
}
