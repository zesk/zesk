<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage World
 */
namespace zesk\World;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use zesk\Doctrine\Model;
use zesk\Doctrine\Trait\AutoID;
use zesk\Doctrine\Trait\CodeName;

/**
 *
 */
#[Entity]
class Province extends Model
{
	use AutoID;
	use CodeName;

	#[ManyToOne]
	#[JoinColumn(name: 'country')]
	public Country $country;
}
