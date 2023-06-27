<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Interface;

/**
 *
 * @author kent
 *
 */
interface Themeable
{
	public function theme(array|string $types, array $arguments = [], array $options = []): ?string;
}
