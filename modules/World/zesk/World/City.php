<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage objects
 */
namespace zesk\World;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\SequenceGenerator;

/**
 */
#[Entity]
class City {
	#[Id, Column(type: 'integer'), SequenceGenerator]
	public null|int $id = null;

	#[Column(type: 'string')]
	public string $name;

	#[OneToOne(targetEntity: County::class)]
	#[JoinColumn(name: 'county')]
	public County $county;

	#[OneToOne(targetEntity: Province::class)]
	#[JoinColumn(name: 'province')]
	public Province $province;
}
