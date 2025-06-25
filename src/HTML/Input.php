<?php declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage HTML
 * @author     kent
 * @copyright  Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace zesk\HTML;

use zesk\ArrayTools;
use zesk\HTML as HTMLBase;

class Input
{
	private static function toAttributes(array|string $attributes): array
	{
		return HTMLBase::toAttributes($attributes);
	}

	public static function submit(string $name, string $value, array|string $attributes = []): string
	{
		$attributes = self::toAttributes($attributes);
		$attributes['name'] = $name;
		$attributes['value'] = $value;
		$attributes['type'] = 'submit';
		$attributes['id'] ??= $name;
		return HTMLBase::tag('input', $attributes, null);
	}

	public static function button(string $name, string $value, array|string $attributes = []): string
	{
		$attributes = self::toAttributes($attributes);
		$attributes['name'] = $name;
		$attributes['value'] = $value;
		$attributes['type'] = 'button';
		$attributes['id'] ??= $name;
		return HTMLBase::tag('input', $attributes, null);
	}

	public static function hidden(string $name, string|array $value, array|string $attributes = []): string
	{
		if (is_array($value)) {
			$result = '';
			$no_key = ArrayTools::isList($value);
			$attributes['id'] = null;
			foreach ($value as $k => $v) {
				$suffix = $no_key ? '[]' : '[' . $k . ']';
				$result .= self::hidden($name . $suffix, $v, $attributes);
			}
			return $result;
		}
		return self::tag('hidden', $name, $value, $attributes);
	}

	public static function tag(string $type, string $name, string $value, array|string $attributes = []): string
	{
		$attributes = self::toAttributes($attributes);
		$type = strtolower($type);
		$attributes['name'] = $name;
		if ($type === 'textarea') {
			return HTMLBase::tag($type, $attributes, htmlspecialchars($value));
		} else {
			$attributes['type'] = $type;
			$attributes['value'] = $value;
		}
		return HTMLBase::tag('input', $attributes, null);
	}
}
