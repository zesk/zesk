<?php declare(strict_types=1);
namespace zesk\Preference\Preference\zesk\Preference;

namespace zesk\Preference;

use zesk\Module as BaseModule;

class Module extends BaseModule {
	/**
	 * Type should be included by Value
	 *
	 * @var array
	 */
	protected array $modelClasses = [
		Value::class,
	];
}
