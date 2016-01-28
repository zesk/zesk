<?php

class View_Date extends View {
	protected $theme = "view/date";

	public function format($set = null) {
		if ($set !== null) {
			return $this->set_option('format', $set);
		}
		return $this->option('format');
	}
	public function relative_min_unit($set = null) {
		if ($set !== null) {
			return $this->set_option('relative_min_unit', $set);
		}
		return $this->option('relative_min_unit');
	}
}
