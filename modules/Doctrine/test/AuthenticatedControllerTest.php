<?php
declare(strict_types=1);

namespace test;

use zesk\Doctrine\DatabaseTestCase;
use zesk\Doctrine\User;
use zesk\HTTP;

class AuthenticatedControllerTest extends DatabaseTestCase
{
	public function test_AuthenticatedController(): void
	{
		$this->application->modules->load('Session');

		require_once __DIR__ . '/TestAuthenticatedController.php';

		$router = $this->application->router();
		$route = $router->addRoute('login', ['controller' => TestAuthenticatedController::class]);
		$request = $this->application->requestFactory()->initializeFromSettings('https://localhost/login');
		$session = $this->application->requireSession($request);

		/* Default permission */
		$user = new User($this->application);

		$matchedRoute = $router->matchRequest($request);
		$this->assertEquals($matchedRoute, $route);
		$response = $route->execute($request);

		$this->assertEquals('Session not authorized', $response->statusMessage());
		$this->assertEquals(HTTP::STATUS_UNAUTHORIZED, $response->status());

		$session->authenticate($user, $request);

		$response = $route->execute($request);

		$this->assertEquals('User does not have permission', $response->statusMessage());
		$this->assertEquals(HTTP::STATUS_UNAUTHORIZED, $response->status());

		/* Default permission for this user */
		$user->setOption('can', true);

		$response = $route->execute($request);
		$this->assertEquals('OK', $response->statusMessage());
		$this->assertEquals(HTTP::STATUS_OK, $response->status());
	}
}
