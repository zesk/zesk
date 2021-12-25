<?php declare(strict_types=1);
class Module_Spectrum extends zesk\Module {
	public const version = "1.1.6";

	public function hook_version() {
		return self::version;
	}
}
