<?php declare(strict_types=1);
namespace zesk;

class Module_Preference extends Module {
	protected array $model_classes = [
		Preference::class,
		Preference_Type::class,
	];
}
