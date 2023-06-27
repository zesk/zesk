<?php
declare(strict_types=1);

namespace zesk\ORM\Test;

use zesk\ORM\ORMBase;
use zesk\ORM\ORMIterator;
use zesk\ORM\Test\Class\Class_TestPerson;

/**
 * @see Class_TestPerson
 * @property ORMIterator Children
 * @property ORMIterator Pets
 * @property ORMIterator Favorite_Pets
 */
class TestPerson extends ORMBase
{
}
