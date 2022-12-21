<?php declare(strict_types=1);
namespace zesk;

class Module_Preference extends Module {
	protected array $modelClasses = [
		Preference::class,
		Preference_Type::class,
	];
}
