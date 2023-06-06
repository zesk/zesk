<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Interface
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\Interface;

interface Formatting {
	/**
	 * Formats this item. If format is blank, use a default format.
	 *
	 * @param string $format
	 * @param array $options
	 * @return string
	 */
	public function format(string $format, array $options = []): string;

	/**
	 * Returns a set of tokens used to format
	 *
	 * @param array $options
	 * @return array
	 */
	public function formatting(array $options = []): array;
}
