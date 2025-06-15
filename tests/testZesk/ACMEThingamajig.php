<?php
declare(strict_types=1);
/**
 *
 */

namespace testZesk;

class ACMEThingamajig
{
	public string $name;

	public function __construct(string $name)
	{
		$this->name = $name;
	}
}
