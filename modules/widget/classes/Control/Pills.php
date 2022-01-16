<?php declare(strict_types=1);
namespace zesk;

class Control_Pills extends Control_Select {
	public function multiple($set = null) {
		return $set === null ? $this->optionBool('multiple') : $this->setOption('multiple', to_bool($set));
	}

	public function one_or_more($set = null) {
		return $set === null ? $this->optionBool('one_or_more') : $this->setOption('one_or_more', to_bool($set));
	}
}
