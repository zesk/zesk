<?php declare(strict_types=1);
namespace zesk;

class View_Theme extends View {
	public function initialize(): void {
		parent::initialize();
		$this->theme = $this->option('theme');
	}
}
