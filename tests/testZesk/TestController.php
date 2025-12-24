<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Application;
use zesk\Controller as ControllerBase;
use zesk\Request;

class TestController extends ControllerBase
{
	protected array $argumentMethods = [];

	protected array $actionMethods = [
		'action_{action}',
		'action_index',
	];

	protected array $beforeMethods = [

	];

	protected array $afterMethods = [

	];

	public function initialize(): void
	{
		parent::initialize();
		$this->application->setOption(__CLASS__, []);
		$this->application->optionAppend(__CLASS__, 'init');
	}

	public function action_index(string $a = '', string $b = ''): string
	{
		$this->application->optionAppend(__CLASS__, [
			$a,
			$b,
		]);
		return "$a|$b";
	}

	public function action_array(string $a, string $b, Request $request): array
	{
		$this->application->optionAppend(__CLASS__, [
			$a,
			$b,
		]);
		return [
			'first' => $a,
			'second' => $b,
			'request' => $request,
		];
	}

	public static function getOption(Application $app): array
	{
		return $app->optionArray(__CLASS__);
	}
}
