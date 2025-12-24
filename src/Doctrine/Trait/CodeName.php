<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Doctrine\Trait;

use Doctrine\ORM\Mapping\Column;

trait CodeName
{
	#[Column(type: 'string')]
	public string $code;

	#[Column(type: 'string')]
	public string $name;
}
