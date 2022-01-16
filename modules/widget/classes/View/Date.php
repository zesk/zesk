<?php declare(strict_types=1);
namespace zesk;

class View_Date extends View {
	public function format($set = null) {
		if ($set !== null) {
			return $this->setOption('format', $set);
		}
		return $this->option('format');
	}

	public function relative_min_unit($set = null) {
		if ($set !== null) {
			return $this->setOption('relative_min_unit', $set);
		}
		return $this->option('relative_min_unit');
	}
}
