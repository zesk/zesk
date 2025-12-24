<?php
declare(strict_types=1);

namespace zesk\Doctrine\Trait;

use Doctrine\ORM\Mapping\Column;
use zesk\Timestamp;

trait Created
{
	#[Column(type: 'timestamp', nullable: false, options: ['default' => 'CURRENT_TIMESTAMP'], generated: true)]
	public Timestamp $created;
}
