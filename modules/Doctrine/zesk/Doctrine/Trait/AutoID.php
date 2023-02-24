<?php declare(strict_types=1);
namespace zesk\Doctrine\Trait;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id as MappingId;

trait AutoID {
	#[MappingId, Column(type: 'integer'), GeneratedValue]
	public null|int $id = null;
}
