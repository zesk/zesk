<?php declare(strict_types=1);
namespace zesk;

class Module_Browser extends Module {
	protected function hook_classes() {
		return [
			__NAMESPACE__ . '\\' . 'Browser',
		];
	}
}
