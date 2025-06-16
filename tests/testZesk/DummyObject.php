<?php declare(strict_types=1);

namespace testZesk;

class DummyObject
{
	public function __set($n, $v): void
	{
		$this->$n = $v;
	}
}
