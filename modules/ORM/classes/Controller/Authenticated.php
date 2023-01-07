<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\ORM;

use zesk\Exception_Authentication;
use zesk\Interface_UserLike;
use zesk\Request;
use zesk\Response;
use zesk\HTTP;
use zesk\Controller_Theme;

/**
 * @see Controller_Theme
 * @author kent
 *
 */
class Controller_Authenticated extends Controller_Theme {
	/**
	 * Current logged in user
	 *
	 * @var Interface_UserLike|null
	 */
	public Interface_UserLike|null $user = null;

	/**
	 * Current session
	 *
	 * @var Interface_Session|null
	 */
	public Interface_Session|null $session = null;

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 * @throws Exception_Authentication
	 */
	protected function hook_before(Request $request, Response $response): void {
		$this->session = $this->application->session($request, false);
		$this->user = $this->session?->user();
		if (!$this->session || !$this->user) {
			$response->setStatus(HTTP::STATUS_UNAUTHORIZED);
		}
	}

	/**
	 * Variables for a template
	 *
	 * @return array
	 */
	public function variables(): array {
		return [
			'user' => $this->user, 'session' => $this->session,
		] + parent::variables();
	}
}
