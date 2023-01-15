<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
interface Interface_Theme {
	public function theme(array|string $types, array $arguments = [], array $options = []): ?string;
}
