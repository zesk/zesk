<?php
declare(strict_types=1);
/**
 * @package testZesk
 * @subpackage Model
 * @copyright Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Model;

//class TestModel extends Model {
//	public string $name = '';
//
//	public string $value = '';
//}
class TestModel extends Model
{
	public string $thing = '';

	public int $thingTwo = 0;

	public function setId(mixed $set): self
	{
		if (is_int($set)) {
			$this->thingTwo = $set;
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function __serializeChild(): array
	{
		return [
			'thing' => $this->thing,
			'thingTwo' => $this->thingTwo,
		];
	}

	/**
	 * @param array $data
	 * @return void
	 */
	public function __unserializeChild(array $data): void
	{
		$this->thing = $data['thing'];
		$this->thingTwo = $data['thingTwo'];
	}
}
