<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * Created on Tue,Sep 22, 09 at 1:16 PM
 */
namespace zesk;

class View_Section_End extends View {
	public function initialize(): void {
		parent::initialize();
		$this->setOption([
			'is_section_end' => true,
			'is_section' => true,
		]);
	}
}
