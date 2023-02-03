<?php
declare(strict_types=1);

namespace zesk;

use ArrayAccess;
use ArrayObject;
use Iterator;

class CaseArray extends ArrayObject implements ArrayAccess {
	public array $nameToCase = [];

	public array $lowNameToValue = [];

	public function __construct(array $array = [], int $flags = 0) {
		parent::__construct($array, $flags, CaseArrayIterator::class);
	}

	/**
	 * @return array
	 */
	public function getArrayCopy(): array {
		$result = [];
		foreach ($this as $key => $value) {
			$result[$key] = $value;
		}
		return $result;
	}

	/**
	 * @return array
	 */
	public function flipped(): array {
		return ArrayTools::valuesMap(array_flip($this->lowNameToValue), $this->nameToCase);
	}

	/**
	 * @return array
	 */
	public function keys(): array {
		return array_values($this->nameToCase);
	}

	/**
	 * @param mixed $key
	 * @return bool
	 */
	public function offsetExists(mixed $key): bool {
		return array_key_exists(strtolower($key), $this->lowNameToValue);
	}

	public function offsetGet(mixed $key): mixed {
		$lowKey = strtolower($key);
		return $this->lowNameToValue[$lowKey] ?? null;
	}

	public function offsetSet(mixed $key, mixed $value): void {
		$lowKey = strtolower($key);

		$this->nameToCase[$lowKey] = $key;
		$this->lowNameToValue[$lowKey]  = $value;
	}

	public function offsetUnset(mixed $key): void {
		$lowKey = strtolower($key);

		unseat($this->nameToCase[$lowKey]);
		unseat($this->lowNameToValue[$lowKey]);
	}

	public function count(): int {
		return count($this->lowNameToValue);
	}

	public function getIterator(): Iterator {
		return new CaseArrayIterator($this);
	}
}
