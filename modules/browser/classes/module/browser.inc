<?php
namespace zesk;

class Module_Browser extends Module {
	protected function hook_classes() {
		return array(
			__NAMESPACE__ . '\\' . 'Browser',
		);
	}
}
