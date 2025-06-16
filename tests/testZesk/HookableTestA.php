<?php
declare(strict_types=1);

namespace testZesk;

use zesk\Application;
use zesk\Hookable;
use zesk\HookMethod;
use zesk\Types;

class HookableTestA extends Hookable
{
	public const HOOK_TEST = __CLASS__ . '::test';

	public static Application $app;

	public function runOurTestHook(array $data)
	{
		$data['ourTestHook'] = microtime(true);
		$data = $this->invokeFilters(self::HOOK_TEST, $data);
		return $data;
	}

	public static function appendTracking(string $whatever): void
	{
		$calls = Types::toArray(self::$app->configuration->get('hookable'));
		$calls[] = $whatever;
		self::$app->configuration->set('hookable', $calls);
	}

	#[HookMethod(handles: self::HOOK_TEST, filter: true)]
	public static function doTheThing(array $data): array
	{
		$data['test_a'] = 99;
		return $data;
	}
}
