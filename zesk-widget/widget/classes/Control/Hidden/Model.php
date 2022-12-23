<?php declare(strict_types=1);
namespace zesk;

class Control_Hidden_Model extends Control_Hidden {
	public function load(): void {
		$x = $this->request->get($this->name());

		try {
			$object = $this->modelFactory($this->class, $x)->fetch();
			$this->value($object);
		} catch (\Exception $e) {
			$this->application->hooks->call('exception', $e);
		}
	}
}
