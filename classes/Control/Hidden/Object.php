<?php
namespace zesk;

class Control_Hidden_Object extends Control_Hidden {
	function load() {
		$x = $this->request->get($this->name());
		try {
			$object = $this->application->object_factory($this->class, $x)->fetch();
			$this->value($object);
		} catch (Exception $e) {
			global $zesk;
			$zesk->hooks->call("exception", $e);
		}
	}
}
