<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Login;

use zesk\Controller as zeskController;
use zesk\Exception_Authentication;
use zesk\Exception_Key;
use zesk\Exception_Unsupported;
use zesk\HTTP;
use zesk\ORM\Exception_ORMNotFound;
use zesk\ORM\User;
use zesk\Request;
use zesk\Response;

/**
 *
 * @author kent
 *
 */
class Controller extends zeskController {
	protected array $argumentMethods = ['arguments'];

	protected array $beforeMethods = [];

	protected array $afterMethods = [];

	protected array $actionMethods = ['action_{METHOD}_{action}'];

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param array $arguments
	 * @param string $action
	 * @return array
	 */
	public function arguments(Request $request, Response $response, array $arguments, string $action): array {
		return [$request, $response];
	}

	public function action_GET_index(Request $request, Response $response): Response {
		$session = $this->application->session($request, false);
		if (!$session) {
			return $response->json()->setData([
				'authenticated' => false, 'userId' => null, 'session' => $session->variables(),
			]);
		}
		$authenticated = $session->authenticated();
		$userId = $authenticated ? $session->userId() : null;
		return $response->json()->setData([
			'authenticated' => $session->authenticated(), 'session' => $session->variables(),
			'userId' => $userId,
		] + ($authenticated ? $session->user()->authenticationData() : []));
	}

	public function action_POST_login(Request $request, Response $response): Response {
		$this->callHook('login');
		$user = $request->get($this->option('requestIdColumn', 'user'));
		$password = $request->get($this->option('requestPasswordColumn', 'password'));

		try {
			$user = $this->handleLogin($user, $password);
			$user->authenticated($request, $response);

			$data = toArray($user->callHookArguments('submit', [$this], []));
			return $response->json()->setData([
				'authenticated' => true, 'user' => $user->id(),
			] + $data);
		} catch (Exception_Authentication $e) {
			$response->setStatus(HTTP::STATUS_UNAUTHORIZED, 'Unauthorized');
			$data = toArray($this->callHookArguments('submit_failed', [$this], []));

			return $response->json()->setData([
				'authenticated' => false, 'message' => 'user-or-password-mismatch',
			] + $data);
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws \zesk\Exception_Semantics
	 */
	public function action_POST_logout(Request $request, Response $response): Response {
		$this->callHook('logout');
		$session = $this->application->session($request, false);
		if ($session) {
			$id = $session->id();
			$session->relinquish();
			$this->application->logger->notice('Session #{id} relinquishd', ['id' => $id]);
		} else {
			$this->application->logger->notice('Logout with no session found in request: Cookies: {cookies}', [
				'cookies' => $request->cookies(),
			]);
		}
		return $response->json()->setData(['logout' => true]);
	}

	/**
	 * @param string $userName
	 * @param string $password
	 * @return User
	 * @throws Exception_Authentication
	 * @throws Exception_Unsupported
	 */
	private function handleLogin(string $userName, string $password): User {
		$user = $this->application->ormFactory(User::class);
		$column_login = $this->option('ormIdColumn', $user->column_login());
		if ($this->option('no_password')) {
			try {
				$user = $this->application->ormRegistry(User::class)->querySelect()->addWhere($column_login, $user)->orm();
				assert($user instanceof User);
				return $user;
			} catch (Exception_Key|Exception_ORMNotFound $e) {
				throw new Exception_Authentication($userName, [], 0, $e);
			}
		}
		/* @var $user User */
		$hashed_password = $this->generateHashedPassword($password);

		try {
			$user = $user->authenticate($hashed_password, false, false);
		} catch (Exception_Authentication $e) {
			/* 2nd chance */
			if ($this->callHookArguments('authenticate', [
				$user, $userName, $password,
			], false)) {
				return $user;
			}

			throw $e;
		}
		return $user;
	}
}
