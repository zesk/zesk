<?php
declare(strict_types=1);

namespace testZesk\Doctrine;

use zesk\Exception\AuthenticationException;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\FileNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\PermissionDenied;
use zesk\Exception\Redirect;
use zesk\Exception\SemanticsException;
use zesk\HTTP;

/**
 * @coversDefaultClass \zesk\Doctrine\AuthenticatedController
 */
class AuthenticatedControllerTest extends DatabaseTestBaseCase
{
	/**
	 * @return void
	 * @throws AuthenticationException
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws FileNotFound
	 * @throws NotFoundException
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws PermissionDenied
	 * @throws Redirect
	 * @throws SemanticsException
	 * @covers ::execute
	 */
	public function test_AuthenticatedController(): void
	{
		$this->testApplication->modules->load('Session');

		$router = $this->testApplication->router();
		$router->clear();

		$route = $router->addRoute('login', ['controller' => TestAuthenticatedController::class]);
		$request = $this->testApplication->requestFactory()->initializeFromSettings('https://localhost/login');
		$session = $this->testApplication->requireSession($request);

		/* Default permission */
		$user = new User($this->testApplication);
		$user->id = 2171;
		$user->nameLast = 'Dude';
		$user->nameFirst = 'The';

		// Mark our user here
		$user->setOption('testMarker', microtime(true));

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
