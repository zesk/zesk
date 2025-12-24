<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\Doctrine\Trait;

use Doctrine\ORM\Mapping\Column;

trait Name
{
	#[Column(type: 'string', nullable: false)]
	public string $name = '';
}
