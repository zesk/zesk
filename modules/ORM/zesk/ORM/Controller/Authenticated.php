<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM;

use zesk\Authentication;
use zesk\Interface\SessionInterface;
use zesk\Userlike;
use zesk\Request;
use zesk\Response;
use zesk\HTTP;
use zesk\Controller;

/**
 * @see ThemeController
 * @author kent
 *
 */
class Controller_Authenticated extends Controller {
	/**
	 * Current logged in user
	 *
	 * @var Userlike|null
	 */
	public Userlike|null $user = null;

	/**
	 * Current session
	 *
	 * @var SessionInterface|null
	 */
	public SessionInterface|null $session = null;

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return void
	 * @throws Authentication
	 */
	protected function before(Request $request, Response $response): void {
		parent::before($request, $response);

		try {
			$this->session = $this->application->requireSession($request);
			$this->user = $this->session->user();
		} catch (Authentication $e) {
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
