<?php declare(strict_types=1);
/**
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
namespace Server\classes\Controller;

/**
 *
 * @author kent
 *
 */
class Controller_Index extends \zesk\Controller_Theme {
	/**
	 *
	 * @var string
	 */
	protected $theme = 'page/manage';

	/**
	 *
	 * @return string
	 */
	public function action_index() {
		$widgets = to_list('disk;services;load;apache;php;configuration');
		return $this->theme('body/dashboard', [
			'widgets' => $widgets,
		]);
	}
}
