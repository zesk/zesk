<?php
namespace zesk;

class View_Selection_Type extends View {
	public function show_editor($set = null) {
		return $set === null ? $this->option_bool('show_editor') : $this->set_option('show_editor', to_bool($set));
	}

	public function label_button($set = null) {
		return $set === null ? $this->option('label_button') : $this->set_option('label_button', $set);
	}
}
