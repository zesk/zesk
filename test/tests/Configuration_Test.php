<?php

namespace zesk;

class Configuration_Test extends Test_Unit {
	public function test_basics() {
		$zesk = zesk();
		$zesk->configuration->path_set("TEST::ROOT", "Hello");
		dump($zesk->configuration->TEST->ROOT->value());
	}
}
