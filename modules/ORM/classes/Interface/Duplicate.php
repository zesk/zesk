<?php
declare(strict_types=1);

namespace zesk\ORM;

interface Interface_Duplicate {
	public function processDuplicate(ORM $orm): void;
}
