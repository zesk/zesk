<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use zesk\Exception\SyntaxException;

/**
 * Abstraction for CSS operations
 *
 * @author kent
 */
class CSS
{
	/**
	 * Array of standard CSS colors and their respective hex codes
	 *
	 * @return string[]
	 */
	public static function colorTable(): array
	{
		return [
			'aliceblue' => 'f0f8ff',
			'antiquewhite' => 'faebd7',
			'aqua' => '00ffff',
			'aquamarine' => '7fffd4',
			'azure' => 'f0ffff',
			'beige' => 'f5f5dc',
			'bisque' => 'ffe4c4',
			'black' => '000000',
			'blanchedalmond' => 'ffebcd',
			'blue' => '0000ff',
			'blueviolet' => '8a2be2',
			'brown' => 'a52a2a',
			'burlywood' => 'deb887',
			'cadetblue' => '5f9ea0',
			'chartreuse' => '7fff00',
			'chocolate' => 'd2691e',
			'coral' => 'ff7f50',
			'cornflowerblue' => '6495ed',
			'cornsilk' => 'fff8dc',
			'crimson' => 'dc143c',
			'cyan' => '00ffff',
			'darkblue' => '00008b',
			'darkcyan' => '008b8b',
			'darkgoldenrod' => 'b8860b',
			'darkgray' => 'a9a9a9',
			'darkgrey' => 'a9a9a9',
			'darkgreen' => '006400',
			'darkkhaki' => 'bdb76b',
			'darkmagenta' => '8b008b',
			'darkolivegreen' => '556b2f',
			'darkorange' => 'ff8c00',
			'darkorchid' => '9932cc',
			'darkred' => '8b0000',
			'darksalmon' => 'e9967a',
			'darkseagreen' => '8fbc8f',
			'darkslateblue' => '483d8b',
			'darkslategray' => '2f4f4f',
			'darkslategrey' => '2f4f4f',
			'darkturquoise' => '00ced1',
			'darkviolet' => '9400d3',
			'deeppink' => 'ff1493',
			'deepskyblue' => '00bfff',
			'dimgray' => '696969',
			'dimgrey' => '696969',
			'dodgerblue' => '1e90ff',
			'firebrick' => 'b22222',
			'floralwhite' => 'fffaf0',
			'forestgreen' => '228b22',
			'fuchsia' => 'ff00ff',
			'gainsboro' => 'dcdcdc',
			'ghostwhite' => 'f8f8ff',
			'gold' => 'ffd700',
			'goldenrod' => 'daa520',
			'gray' => '808080',
			'grey' => '808080',
			'green' => '008000',
			'greenyellow' => 'adff2f',
			'honeydew' => 'f0fff0',
			'hotpink' => 'ff69b4',
			'indianred' => 'cd5c5c',
			'indigo' => '4b0082',
			'ivory' => 'fffff0',
			'khaki' => 'f0e68c',
			'lavender' => 'e6e6fa',
			'lavenderblush' => 'fff0f5',
			'lawngreen' => '7cfc00',
			'lemonchiffon' => 'fffacd',
			'lightblue' => 'add8e6',
			'lightcoral' => 'f08080',
			'lightcyan' => 'e0ffff',
			'lightgoldenrodyellow' => 'fafad2',
			'lightgray' => 'd3d3d3',
			'lightgrey' => 'd3d3d3',
			'lightgreen' => '90ee90',
			'lightpink' => 'ffb6c1',
			'lightsalmon' => 'ffa07a',
			'lightseagreen' => '20b2aa',
			'lightskyblue' => '87cefa',
			'lightslategray' => '778899',
			'lightslategrey' => '778899',
			'lightsteelblue' => 'b0c4de',
			'lightyellow' => 'ffffe0',
			'lime' => '00ff00',
			'limegreen' => '32cd32',
			'linen' => 'faf0e6',
			'magenta' => 'ff00ff',
			'maroon' => '800000',
			'mediumaquamarine' => '66cdaa',
			'mediumblue' => '0000cd',
			'mediumorchid' => 'ba55d3',
			'mediumpurple' => '9370d8',
			'mediumseagreen' => '3cb371',
			'mediumslateblue' => '7b68ee',
			'mediumspringgreen' => '00fa9a',
			'mediumturquoise' => '48d1cc',
			'mediumvioletred' => 'c71585',
			'midnightblue' => '191970',
			'mintcream' => 'f5fffa',
			'mistyrose' => 'ffe4e1',
			'moccasin' => 'ffe4b5',
			'navajowhite' => 'ffdead',
			'navy' => '000080',
			'oldlace' => 'fdf5e6',
			'olive' => '808000',
			'olivedrab' => '6b8e23',
			'orange' => 'ffa500',
			'orangered' => 'ff4500',
			'orchid' => 'da70d6',
			'palegoldenrod' => 'eee8aa',
			'palegreen' => '98fb98',
			'paleturquoise' => 'afeeee',
			'palevioletred' => 'd87093',
			'papayawhip' => 'ffefd5',
			'peachpuff' => 'ffdab9',
			'peru' => 'cd853f',
			'pink' => 'ffc0cb',
			'plum' => 'dda0dd',
			'powderblue' => 'b0e0e6',
			'purple' => '800080',
			'red' => 'ff0000',
			'rosybrown' => 'bc8f8f',
			'royalblue' => '4169e1',
			'saddlebrown' => '8b4513',
			'salmon' => 'fa8072',
			'sandybrown' => 'f4a460',
			'seagreen' => '2e8b57',
			'seashell' => 'fff5ee',
			'sienna' => 'a0522d',
			'silver' => 'c0c0c0',
			'skyblue' => '87ceeb',
			'slateblue' => '6a5acd',
			'slategray' => '708090',
			'slategrey' => '708090',
			'snow' => 'fffafa',
			'springgreen' => '00ff7f',
			'steelblue' => '4682b4',
			'tan' => 'd2b48c',
			'teal' => '008080',
			'thistle' => 'd8bfd8',
			'tomato' => 'ff6347',
			'turquoise' => '40e0d0',
			'violet' => 'ee82ee',
			'wheat' => 'f5deb3',
			'white' => 'ffffff',
			'whitesmoke' => 'f5f5f5',
			'yellow' => 'ffff00',
			'yellowgreen' => '9acd32',
		];
	}

	/**
	 * Add a class to another CSS class for inclusion in HTML
	 *
	 * @param string|array $classes
	 * @param string|array $add
	 * @return string
	 */
	public static function addClass(string|array $classes, string|array $add = ''): string
	{
		if (is_array($classes)) {
			$classes = implode(' ', $classes);
		}
		return $add ? Lists::appendUnique($classes, $add, ' ') : $classes;
	}

	/**
	 * Remove a class from a list of classes
	 * @param string|array $classes
	 * @param string $remove
	 * @return string
	 */
	public static function removeClass(string|array $classes, string $remove = ''): string
	{
		if (is_array($classes)) {
			$classes = implode(' ', $classes);
		}
		return Lists::keysRemove($classes, $remove, ' ');
	}

	/**
	 * Given a color name, determine the hex code for the color.
	 *
	 * If color not found, then return default value.
	 *
	 * @param string $text
	 * @param string $default
	 * @return string
	 */
	public static function colorLookup(string $text, string $default = ''): string
	{
		$colors = self::colorTable();
		return $colors[strtolower($text)] ?? $default;
	}

	/**
	 * Convert an RGB value to a hex value
	 *
	 * @param array $rgb Array of three values between 0 and 255
	 * @return string
	 */
	public static function rgbToHex(array $rgb): string
	{
		$color = '';
		foreach ($rgb as $c) {
			$c = Number::clamp(0, $c, 255);
			$c = strtoupper(dechex($c));
			if (strlen($c) === 1) {
				$c = "0$c";
			}
			$color .= $c;
		}
		return $color;
	}

	/**
	 * Convert an RGB value to a hex code, including the # prefix
	 *
	 * @param array $rgb Array of three between 0 and 255
	 * @return string
	 */
	public static function colorFormat(array $rgb): string
	{
		return '#' . self::rgbToHex($rgb);
	}

	/**
	 * Parse a color and convert it to hexadecimal color
	 *
	 * @param string $text Color value
	 * @return string
	 * @throws SyntaxException
	 */
	public static function colorNormalize(string $text): string
	{
		$x = self::colorParse($text) + [0, 0, 0];
		return self::rgbToHex($x);
	}

	/**
	 * Does this string represent a CSS color value?
	 *
	 * @param string $text
	 * @return boolean
	 */
	public static function isColor(string $text): bool
	{
		try {
			self::colorParse($text);
			return true;
		} catch (SyntaxException) {
			return false;
		}
	}

	/**
	 * Parse a color value from a CSS file
	 *
	 * @param string $text
	 * @return array ($r, $g, $b) returned as a list
	 * @return array
	 * @throws SyntaxException
	 * @todo Does not support rgba
	 */
	public static function colorParse(string $text): array
	{
		$text = trim($text);
		if (strlen($text) == 0) {
			throw new SyntaxException('Blank color');
		}
		$matches = [];
		if (preg_match('/^rgb\(([0-9,]+)\)$/', $text, $matches)) {
			$colors = explode(',', $matches[1]);
			if (count($colors) === 3) {
				foreach ($colors as $i => $c) {
					if ($c <= 0 || $c >= 255) {
						$c = Number::clamp(0, $c, 255);
						// TODO Warn or something
					}
					$colors[$i] = intval($c);
				}
				return $colors;
			}
		}
		if ($text[0] == '#') {
			$text = substr($text, 1);
		} else {
			$text = self::colorLookup($text, $text);
		}
		$text_len = strlen($text);
		if ($text_len !== 3 && $text_len !== 6) {
			throw new SyntaxException('Invalid color text length "{text}" (3 or 6 chars)', ['text' => $text]);
		}
		$text = strtolower($text);
		if (!preg_match('/^[0-9a-f]+$/', $text)) {
			throw new SyntaxException('Invalid color text characters "{text}" (hex only)', ['text' => $text]);
		}
		$text_len = intval($text_len / 3);
		$result = [];
		for ($i = 0; $i < 3; $i += 1) {
			$v = substr($text, $i * $text_len, $text_len);
			if ($text_len == 1) {
				$v = $v . $v;
			}
			$result[] = hexdec($v);
		}
		return $result;
	}
}
