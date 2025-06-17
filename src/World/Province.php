<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 * @package    zesk
 * @subpackage World
 */

namespace zesk\World;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\EntityListeners;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use zesk\Doctrine\Model;
use zesk\Doctrine\ModelListener;
use zesk\Doctrine\Trait\AutoID;
use zesk\Doctrine\Trait\CodeName;

/**
 *
 */
#[Entity]
#[EntityListeners([ModelListener::class])]
class Province extends Model
{
	use AutoID;
	use CodeName;

	#[ManyToOne]
	#[JoinColumn(name: 'country')]
	public Country $country;
}
