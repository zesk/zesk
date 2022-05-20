<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:22:54 EDT 2008
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Select_File extends Control_Select {
	/**
	 *
	 * @param string $options
	 */
	public function initialize(): void {
		parent::initialize();
		$this->setOption('novalue', '');
	}

	/**
	 *
	 * @return mixed[]
	 */
	public function hook_options() {
		$map = Directory::ls($this->option('path', '/data/files/'), $this->option('filter', '/.*\.[A-Za-z0-9]+/'));
		$opts = [];

		foreach ($map as $k) {
			$opts[$map[$k]] = $map[$k];
		}
		return $opts;
	}
}
