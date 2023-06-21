<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Login
 * @author Kent Davidson
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Login;

use zesk\Doctrine\User;
use zesk\Controller as zeskController;
use zesk\Exception\Authentication;
use zesk\Exception\KeyNotFound;
use zesk\Exception\Semantics;
use zesk\Exception\Unsupported;
use zesk\HTTP;
use zesk\Interface\Userlike;
use zesk\Request;
use zesk\Response;
use zesk\Timestamp;
use zesk\Types;

/**
 *
 * @author kent
 *
 */
class Controller extends zeskController {
	public const HOOK_LOGIN = __CLASS__ . '::login';

	public const HOOK_LOGIN_SUCCESS = __CLASS__ . '::loginSuccess';

	public const HOOK_LOGIN_FAILED = __CLASS__ . '::loginFailed';

	public const HOOK_LOGOUT = __CLASS__ . '::logout';

	/**
	 * @var string
	 */
	public const OPTION_PASSWORD_HASH_ALGORITHM = 'hashAlgorithm';

	/**
	 * @var string
	 */
	public const DEFAULT_PASSWORD_HASH_ALGORITHM = self::PASSWORD_HASH_ALGORITHM_SHA1;

	/**
	 * Current, secure
	 */
	public const PASSWORD_HASH_ALGORITHM_SHA1 = 'sha1';

	/**
	 * Legacy, not secure
	 */
	public const PASSWORD_HASH_ALGORITHM_MD5 = 'md5';

	/**
	 * @var array
	 */
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

	private function _baseResponseData(): array {
		return ['now' => Timestamp::now()->iso8601()];
	}

	public function action_OPTIONS_index(Request $request, Response $response): Response {
		return $this->handleOPTIONS($response, 'index');
	}

	public function action_GET_index(Request $request, Response $response): Response {
		$responseData = $this->_baseResponseData();
		$session = $this->application->session($request, false);
		if (!$session) {
			return $response->json()->setData($responseData + [
				'authenticated' => false, 'userId' => null, 'session' => $session->variables(),
			]);
		}
		$authenticated = $session->isAuthenticated();
		return $response->json()->setData($responseData + [
			'authenticated' => $session->isAuthenticated(),
		] + ($authenticated ? $session->user()->authenticationData() : []));
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Unsupported
	 * @throws \ReflectionException
	 * @throws \zesk\Exception\ParameterException
	 */
	public function action_POST_index(Request $request, Response $response): Response {
		/**
		 * Allow hooks to intercept and handle on their own.
		 */
		try {
			$loginHookResult = $this->invokeFilters(self::HOOK_LOGIN, $response, [$request]);
			if ($loginHookResult instanceof Response) {
				return $response;
			}
		} catch (Authentication $e) {
			$response->setStatus(HTTP::STATUS_UNAUTHORIZED, 'Unauthorized');
			// Done calling hooks
			return $response->json()->setData([
				'authenticated' => false, 'message' => $e->getMessage(),
			] + $this->_baseResponseData());
		}
		$user = $request->get($this->option('requestIdColumn', 'user'));
		$password = $request->get($this->option('requestPasswordColumn', 'password'));

		try {
			$user = $this->handleLogin($user, $password);
			$user->authenticated($request, $response);

			$data = Types::toArray($user->invokeFilters(self::HOOK_LOGIN_SUCCESS, [], [$this]));
			return $response->json()->appendData([
				'authenticated' => true, 'user' => $user->id(),
			] + $data + $this->_baseResponseData());
		} catch (Authentication $e) {
			$response->setStatus(HTTP::STATUS_UNAUTHORIZED, 'Unauthorized');
			$data = Types::toArray($user->invokeFilters(self::HOOK_LOGIN_FAILED, [], [$this]));

			return $response->json()->setData([
				'authenticated' => false, 'message' => 'user-or-password-mismatch',
			] + $data + $this->_baseResponseData());
		}
	}

	/**
	 * @param Request $request
	 * @param Response $response
	 * @return Response
	 * @throws Semantics
	 */
	public function action_DELETE_index(Request $request, Response $response): Response {
		$this->invokeHooks(self::HOOK_LOGOUT);
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

	private function generateHashedPassword(string $password): string {
		return match ($this->option(self::OPTION_PASSWORD_HASH_ALGORITHM, self::DEFAULT_PASSWORD_HASH_ALGORITHM)) {
			self::PASSWORD_HASH_ALGORITHM_MD5 => md5($password),
			default => sha1($password),
		};
	}

	/**
	 * @param string $userName
	 * @param string $password
	 * @return Userlike
	 * @throws Authentication
	 * @throws Unsupported
	 */
	private function handleLogin(string $userName, string $password): Userlike {
		$repo = $this->application->entityManager()->getRepository($this->optionString('userClass'));
		$user = $repo->findOneBy(['code' => $userName]);
		if (!$user) {
			throw new Authentication('{userName} failed', ['userName' => $userName]);
		}
		$hashed_password = $this->generateHashedPassword($password);

		return $user->authenticate($hashed_password);
	}
}
