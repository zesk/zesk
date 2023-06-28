<?php
declare(strict_types=1);

namespace zesk;

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
}
