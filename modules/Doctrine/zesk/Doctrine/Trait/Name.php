<?php declare(strict_types=1);
namespace zesk\Doctrine\Trait;

use Doctrine\ORM\Mapping\Column;

trait Name
{
	#[Column(type: 'string', nullable: false)]
	public string $name = '';
}
