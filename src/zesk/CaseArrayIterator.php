<?php
declare(strict_types=1);

namespace zesk;

use ArrayIterator;

class CaseArrayIterator extends ArrayIterator
{
	private CaseArray $target;

	private mixed $item;

	public function __construct(CaseArray $array, $flags = 0)
	{
		parent::__construct();
		$this->target = $array;
		$this->item = null;
	}

	public function current(): mixed
	{
		return $this->item;
	}

	public function next(): void
	{
		$this->item = next($this->target->lowNameToValue);
	}

	public function key(): int|null|string
	{
		return $this->target->nameToCase[key($this->target->lowNameToValue)];
	}

	public function valid(): bool
	{
		return $this->item !== false;
	}

	public function rewind(): void
	{
		$this->item = reset($this->target->lowNameToValue);
	}
}
