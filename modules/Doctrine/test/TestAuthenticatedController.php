<?php declare(strict_types=1);

namespace test;

use zesk\Doctrine\AuthenticatedController;
use zesk\Request;
use zesk\Response;

class TestAuthenticatedController extends AuthenticatedController
{
	protected array $argumentMethods = ['arguments'];

	protected array $actionMethods = ['action_default'];

	public function arguments(Request $request, Response $response): array
	{
		return [$response, $request];
	}

	/**
	 * @param Response $response
	 * @return Response
	 * @see self::action_default()
	 */
	public function action_default(Response $response): Response
	{
		return $response->setContent('Yes');
	}
}
