<?php
declare(strict_types=1);

namespace zesk\ORM\Interface;

use zesk\ORM\ORMBase;

interface ORMDuplicateInterface {
	public function duplicate(ORMBase $source): ORMBase;
}
