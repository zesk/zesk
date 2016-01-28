<?php
class Control_Checklist_Bitmask extends Control_Checklist {
	private function bitmaskToArray($value) {
		$x = 0;
		$result = array();
		for($i = 0; $i < 31; $i++) {
			if ($value === 0) {
				break;
			}
			$x = 1 << $i;
			if (($value & $x) !== 0) {
				$result[] = $x;
				$value = $value & ~$x;
			}
		}
		return $result;
	}
	protected function hook_object_value(Model $object) {
		$column = $this->column();
		$value = $object->get($column);
		return $this->bitmaskToArray($value);
	}
	protected function load() {
		$name = $this->name();
		$result = 0;
		foreach ($this->request->geta($name) as $item) {
			$result = $result | intval($item);
		}
		$this->value($result);
	}
}