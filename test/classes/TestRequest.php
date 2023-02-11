<?php
declare(strict_types=1);

namespace zesk;

/**
 * See if we can customize system objects
 */
class TestRequest extends Request {
	/**
	 * @return array
	 */
	public function data(): array {
		return $this->data + ['testData' => true];
	}
}
