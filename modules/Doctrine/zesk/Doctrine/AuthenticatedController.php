<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Doctrine;

use zesk\Controller;
use zesk\Exception\Authentication;
use zesk\Exception\ClassNotFound;
use zesk\HTTP;
use zesk\Interface\SessionInterface;
use zesk\Interface\Userlike;
use zesk\Request;
use zesk\Response;

/**
 * @see ThemeController
 * @author kent
 *
 */
class AuthenticatedController extends Controller {

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
	 * @throws ClassNotFound
	 * @throws Authentication
	 */
	protected function before(Request $request, Response $response): void {
		parent::before($request, $response);

		$this->session = $this->application->requireSession($request);
		if (!$this->session->isAuthenticated()) {
			throw new Authentication("Session not authorized");
		}
		try {
			$this->user = $this->application->requireUser($request);
		} catch (ClassNotFound $e) {
			$response->setStatus(HTTP::STATUS_INTERNAL_SERVER_ERROR, "No class");
			throw $e;
		}
		if (!$this->user->can(get_class($this) . "::*")) {
			throw new Authentication("User not authorized");
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
