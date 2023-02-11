<?php
declare(strict_types=1);

namespace zesk;

class Configuration_Parser_Array extends Configuration_Parser {
	public function initialize(): void {
		if (!is_array($this->content)) {
			$this->content = toArray($this->content);
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
