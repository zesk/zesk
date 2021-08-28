<?php

namespace zesk;

class Configuration_Parser_Array extends Configuration_Parser {
	public function initialize() {
		if (!is_array($this->content)) {
			$this->content = to_array($this->content);
		}
	}

	public function validate() {
		return is_array($this->content);
	}

	/**
	 * @return Interface_Settings
	 */
	public function process() {
		foreach ($this->content as $key => $value) {
			$key = strtr($key, array(
				"___" => "\\",
				"__" => "::",
			));
			$this->settings->set($key, $value);
		}
		return $this;
	}
}
