<?php
declare(strict_types=1);

namespace zesk\Job;

use Throwable;
use zesk\Controller as BaseController;
use zesk\Authentication;
use zesk\Exception\NotFoundException;
use zesk\ORM\ORMEmpty;
use zesk\Request;
use zesk\Response;

class Controller extends BaseController
{
	public const OPTION_REQUIRE_USER = 'requireUser';

	public const OPTION_USER_PERMISSION = 'userPermission';

	public const DEFAULT_USER_PERMISSION = 'view';

	protected array $argumentMethods = [
		'arguments_{METHOD}_{action}',
	];

	protected array $actionMethods = [
		'action_{METHOD}_{action}',
	];

	protected array $beforeMethods = [];

	protected array $afterMethods = [];

	/**
	 * @param Request $request
	 * @param Response $response
	 * @param array $arguments
	 * @return array
	 * @throws Authentication
	 * @throws NotFoundException
	 */
	public function arguments_GET_monitor(Request $request, Response $response, array $arguments): array
	{
		$id = $arguments[0];
		if ($id instanceof Job) {
			$job = $id;
		} elseif (is_int($id) || is_string($id)) {
			try {
				$job = new Job($this->application, $id);
				$job = $job->fetch();
			} catch (Throwable $t) {
				throw new NotFoundException('No job of {id} found', ['id' => $id], 0, $t);
			}
		} else {
			throw new NotFoundException('No job id');
		}
		if ($this->optionBool(self::OPTION_REQUIRE_USER)) {
			try {
				$user = $this->application->requireUser($request);
			} catch (Throwable $t) {
				throw new NotFoundException('No job of {id} found', ['id' => $id], 0, $t);
			}
			$permission = $this->optionString(self::OPTION_USER_PERMISSION, self::DEFAULT_USER_PERMISSION);
			if ($permission && !$user->can($permission, $job)) {
				throw new Authentication('Not allowed to {permission} {id}', [
					'permission' => $permission, 'id' => $id,
				]);
			}
		}
		return [$job, $response];
	}

	/**
	 * @param Job $job
	 * @param Response $response
	 * @return Response
	 * @throws NotFoundException
	 */
	public function action_GET_monitor(Job $job, Response $response): Response
	{
		try {
			$result = [
				'id' => $job->id(), 'message' => $job->status,
			];
		} catch (ORMEmpty $e) {
			throw new NotFoundException('Job id fetch', [], 0, $e);
		}
		$progress = $job->progress;
		if ($progress) {
			$result['progress'] = $progress;
		}
		if (!$job->completed) {
			try {
				$result['wait'] = $job->refreshInterval();
			} catch (Throwable $e) {
				$result['wait'] = 60;
				$result['wait*'] = $e::class;
			}
		} else {
			$result['progress'] = 100;
			$result['completed'] = $job->completed;

			try {
				if ($job->hasData('content')) {
					$result['content'] = $job->data('content');
				}
			} catch (Throwable $e) {
				$result['content'] = null;
				$result['content*'] = $e::class;
			}
		}
		return $response->json()->setData($result);
	}
}
