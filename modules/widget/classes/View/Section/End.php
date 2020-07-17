<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author kent <kent@marketacumen.com>
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Tue,Sep 22, 09 at 1:16 PM
 */
namespace zesk;

class View_Section_End extends View {
	public function initialize() {
		parent::initialize();
		$this->set_option(array(
			'is_section_end' => true,
			'is_section' => true,
		));
	}
}
