<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM;

use zesk\Exception_Authentication;
use zesk\Interface_Session;
use zesk\Interface_UserLike;
use zesk\Request;
use zesk\Response;
use zesk\HTTP;
use zesk\Controller;

/**
 * @see Controller_Theme
 * @author kent
 *
 */
class Controller_Authenticated extends Controller {
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
	protected function before(Request $request, Response $response): void {
		parent::before($request, $response);

		try {
			$this->session = $this->application->requireSession($request);
			$this->user = $this->session->user();
		} catch (Exception_Authentication $e) {
			$response->setStatus(HTTP::STATUS_UNAUTHORIZED);

			throw $e;
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
