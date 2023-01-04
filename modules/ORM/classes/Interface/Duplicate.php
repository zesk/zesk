<?php
declare(strict_types=1);

namespace zesk\ORM;

interface Interface_Duplicate {
	public function duplicate(ORMBase $source): ORMBase;
}
