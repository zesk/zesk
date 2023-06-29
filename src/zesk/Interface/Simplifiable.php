<?php declare(strict_types=1);

namespace zesk\Interface;

interface Simplifiable
{
	/**
	 * Converts into a value which can ultimately be represented in JSON
	 *
	 * @return int|float|array|string
	 */
	public function simplify(): int|float|array|string;
}
