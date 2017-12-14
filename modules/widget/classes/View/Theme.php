<?php
namespace zesk;

class View_Theme extends View {
	function initialize() {
		parent::initialize();
		$this->theme = $this->option('theme');
	}
}
