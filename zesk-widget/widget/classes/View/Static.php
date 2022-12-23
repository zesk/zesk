<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage view
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * Created on Sun Apr 04 21:09:20 EDT 2010 21:09:20
 */
namespace zesk;

class View_Static extends View_Text {
	public function text($set = null) {
		return $set !== null ? $this->setOption('text', $set) : $this->option('text');
	}

	public function render(): string {
		$this->value($this->option('text', ''));
		return parent::render();
	}
}
