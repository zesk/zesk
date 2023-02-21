<?php
declare(strict_types=1);

namespace zesk\Configuration\Parser;

use zesk\Configuration\Parser;
use zesk\Types;

class ArrayParser extends Parser {
	public function initialize(): void {
		if (!is_array($this->content)) {
			$this->content = Types::toArray($this->content);
		}
	}

	public function validate(): bool {
		return is_array($this->content);
	}

	/**
	 */
	public function process(): void {
		foreach ($this->content as $key => $value) {
			$key = strtr($key, [
				'___' => '\\',
				'__' => '::',
			]);
			$this->settings->set($key, $value);
		}
	}
}
