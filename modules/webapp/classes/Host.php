<?php
namespace zesk\WebApp;

use zesk\Hookable;
use zesk\Exception_Parameter;
use zesk\Application;

class Host extends Hookable {
	public function __construct(Application $application, array $structure) {
		parent::__construct($application, $structure);
		$errors = $this->validate_structure();
		if (count($errors) !== 0) {
			throw new Exception_Parameter("Errors in structure: {keys}", array(
				'keys' => array_keys($errors),
			));
		}
	}

	public function validate_structure() {
		$errors = array();
		$code = $this->code;
		if (empty($code)) {
			$errors['code'] = "code is required";
		} elseif (preg_match("/[-_a-zA-z0-9 ]/", $code)) {
			$errors['code'] = "code has incorrect values";
		}
		$name = trim($this->name);
		if (empty($name)) {
			$errors['name'] = "name is required";
		}
		$path = $this->path;
		if (empty($path)) {
			$errors['path'] = "path is required";
		} else {
			$path = $this->application->paths->expand($path);
			if (!is_dir($path)) {
				$errors['path'] = "path must be a directory";
			}
		}
		$type = $this->type;
		if (!empty($type)) {
			if (array_key_exists($type, self::$types)) {
				$errors['type'] = "invalid type, must be one of: " . implode(",", array_keys(self::$types));
			}
		}
	}
}
