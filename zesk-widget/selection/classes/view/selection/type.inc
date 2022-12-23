<?php declare(strict_types=1);
namespace zesk;

class View_Selection_Type extends View {
	public function show_editor($set = null) {
		return $set === null ? $this->optionBool('show_editor') : $this->setOption('show_editor', toBool($set));
	}

	public function label_button($set = null) {
		return $set === null ? $this->option('label_button') : $this->setOption('label_button', $set);
	}
}
