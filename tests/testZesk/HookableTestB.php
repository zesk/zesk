<?php declare(strict_types=1);

namespace testZesk;

use zesk\HookMethod;

class HookableTestB extends HookableTestA
{
	#[HookMethod(handles: self::HOOK_TEST, filter: true)]
	public function requiresObjectHookB(array $data): array
	{
		$data['test_b'] = 99;
		return $data;
	}

	#[HookMethod(handles: self::HOOK_TEST, filter: true)]
	public static function hook_test2(array $data): array
	{
		$data['test2'] = '2';
		return $data;
	}

	#[HookMethod(handles: self::HOOK_TEST, filter: true)]
	public function hook_test3(array $data): array
	{
		$data['test3'] = 'three';
		$data['test1a'] = 'three not 1a';
		$data['dude'] = 'the';
		$data['nice'] = 'pony';
		return $data;
	}
}
