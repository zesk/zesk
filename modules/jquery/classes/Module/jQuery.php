<?php declare(strict_types=1);
namespace zesk;

class Module_jQuery extends Module_JSLib {
	public function initialize(): void {
		$this->application->share_path($this->path . '/share-tools', 'jquery-tools');
	}
}
