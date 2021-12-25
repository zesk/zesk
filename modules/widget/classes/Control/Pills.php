<?php declare(strict_types=1);
namespace zesk;

class Control_Pills extends Control_Select {
	public function multiple($set = null) {
		return $set === null ? $this->option_bool('multiple') : $this->set_option('multiple', to_bool($set));
	}

	public function one_or_more($set = null) {
		return $set === null ? $this->option_bool('one_or_more') : $this->set_option('one_or_more', to_bool($set));
	}
}
