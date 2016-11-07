<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/zesk/css.inc $
 * @package zesk
 * @subpackage system
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2013, Market Acumen, Inc.
 */

namespace zesk;

/**
 * Abstraction for CSS operations
 * 
 * @author kent
 */
class CSS {
	
	/**
	 * Array of standard CSS colors and their respective hex codes
	 * 
	 * @return array
	 */
	public static function color_table() {
		static $colors = array(
			"aliceblue" => "f0f8ff", 
			"antiquewhite" => "faebd7", 
			"aqua" => "00ffff", 
			"aquamarine" => "7fffd4", 
			"azure" => "f0ffff", 
			"beige" => "f5f5dc", 
			"bisque" => "ffe4c4", 
			"black" => "000000", 
			"blanchedalmond" => "ffebcd", 
			"blue" => "0000ff", 
			"blueviolet" => "8a2be2", 
			"brown" => "a52a2a", 
			"burlywood" => "deb887", 
			"cadetblue" => "5f9ea0", 
			"chartreuse" => "7fff00", 
			"chocolate" => "d2691e", 
			"coral" => "ff7f50", 
			"cornflowerblue" => "6495ed", 
			"cornsilk" => "fff8dc", 
			"crimson" => "dc143c", 
			"cyan" => "00ffff", 
			"darkblue" => "00008b", 
			"darkcyan" => "008b8b", 
			"darkgoldenrod" => "b8860b", 
			"darkgray" => "a9a9a9", 
			"darkgrey" => "a9a9a9", 
			"darkgreen" => "006400", 
			"darkkhaki" => "bdb76b", 
			"darkmagenta" => "8b008b", 
			"darkolivegreen" => "556b2f", 
			"darkorange" => "ff8c00", 
			"darkorchid" => "9932cc", 
			"darkred" => "8b0000", 
			"darksalmon" => "e9967a", 
			"darkseagreen" => "8fbc8f", 
			"darkslateblue" => "483d8b", 
			"darkslategray" => "2f4f4f", 
			"darkslategrey" => "2f4f4f", 
			"darkturquoise" => "00ced1", 
			"darkviolet" => "9400d3", 
			"deeppink" => "ff1493", 
			"deepskyblue" => "00bfff", 
			"dimgray" => "696969", 
			"dimgrey" => "696969", 
			"dodgerblue" => "1e90ff", 
			"firebrick" => "b22222", 
			"floralwhite" => "fffaf0", 
			"forestgreen" => "228b22", 
			"fuchsia" => "ff00ff", 
			"gainsboro" => "dcdcdc", 
			"ghostwhite" => "f8f8ff", 
			"gold" => "ffd700", 
			"goldenrod" => "daa520", 
			"gray" => "808080", 
			"grey" => "808080", 
			"green" => "008000", 
			"greenyellow" => "adff2f", 
			"honeydew" => "f0fff0", 
			"hotpink" => "ff69b4", 
			"indianred" => "cd5c5c", 
			"indigo" => "4b0082", 
			"ivory" => "fffff0", 
			"khaki" => "f0e68c", 
			"lavender" => "e6e6fa", 
			"lavenderblush" => "fff0f5", 
			"lawngreen" => "7cfc00", 
			"lemonchiffon" => "fffacd", 
			"lightblue" => "add8e6", 
			"lightcoral" => "f08080", 
			"lightcyan" => "e0ffff", 
			"lightgoldenrodyellow" => "fafad2", 
			"lightgray" => "d3d3d3", 
			"lightgrey" => "d3d3d3", 
			"lightgreen" => "90ee90", 
			"lightpink" => "ffb6c1", 
			"lightsalmon" => "ffa07a", 
			"lightseagreen" => "20b2aa", 
			"lightskyblue" => "87cefa", 
			"lightslategray" => "778899", 
			"lightslategrey" => "778899", 
			"lightsteelblue" => "b0c4de", 
			"lightyellow" => "ffffe0", 
			"lime" => "00ff00", 
			"limegreen" => "32cd32", 
			"linen" => "faf0e6", 
			"magenta" => "ff00ff", 
			"maroon" => "800000", 
			"mediumaquamarine" => "66cdaa", 
			"mediumblue" => "0000cd", 
			"mediumorchid" => "ba55d3", 
			"mediumpurple" => "9370d8", 
			"mediumseagreen" => "3cb371", 
			"mediumslateblue" => "7b68ee", 
			"mediumspringgreen" => "00fa9a", 
			"mediumturquoise" => "48d1cc", 
			"mediumvioletred" => "c71585", 
			"midnightblue" => "191970", 
			"mintcream" => "f5fffa", 
			"mistyrose" => "ffe4e1", 
			"moccasin" => "ffe4b5", 
			"navajowhite" => "ffdead", 
			"navy" => "000080", 
			"oldlace" => "fdf5e6", 
			"olive" => "808000", 
			"olivedrab" => "6b8e23", 
			"orange" => "ffa500", 
			"orangered" => "ff4500", 
			"orchid" => "da70d6", 
			"palegoldenrod" => "eee8aa", 
			"palegreen" => "98fb98", 
			"paleturquoise" => "afeeee", 
			"palevioletred" => "d87093", 
			"papayawhip" => "ffefd5", 
			"peachpuff" => "ffdab9", 
			"peru" => "cd853f", 
			"pink" => "ffc0cb", 
			"plum" => "dda0dd", 
			"powderblue" => "b0e0e6", 
			"purple" => "800080", 
			"red" => "ff0000", 
			"rosybrown" => "bc8f8f", 
			"royalblue" => "4169e1", 
			"saddlebrown" => "8b4513", 
			"salmon" => "fa8072", 
			"sandybrown" => "f4a460", 
			"seagreen" => "2e8b57", 
			"seashell" => "fff5ee", 
			"sienna" => "a0522d", 
			"silver" => "c0c0c0", 
			"skyblue" => "87ceeb", 
			"slateblue" => "6a5acd", 
			"slategray" => "708090", 
			"slategrey" => "708090", 
			"snow" => "fffafa", 
			"springgreen" => "00ff7f", 
			"steelblue" => "4682b4", 
			"tan" => "d2b48c", 
			"teal" => "008080", 
			"thistle" => "d8bfd8", 
			"tomato" => "ff6347", 
			"turquoise" => "40e0d0", 
			"violet" => "ee82ee", 
			"wheat" => "f5deb3", 
			"white" => "ffffff", 
			"whitesmoke" => "f5f5f5", 
			"yellow" => "ffff00", 
			"yellowgreen" => "9acd32"
		);
		
		return $colors;
	}
	
	/**
	 * Add a class to another CSS class for inclusion in HTML
	 * 
	 * @param string $classes
	 * @param string $add
	 * @return string
	 */
	public static function add_class($classes, $add = null) {
		if (is_array($classes)) {
			$classes = implode(" ", $classes);
		}
		return $add ? Lists::append_unique($classes, $add, " ") : $classes;
	}
	
	/**
	 * Remove a class from a list of classes
	 * @param string $classes
	 * @param string $remove
	 * @return string
	 */
	public static function remove_class($classes, $remove = null) {
		if (is_array($classes)) {
			$classes = implode(" ", $classes);
		}
		return Lists::remove($classes, $remove, " ");
	}
	
	/**
	 * Given a color name, determine the hex code for the color. 
	 * 
	 * If color not found, then return default value.
	 * 
	 * @param string $text
	 * @param mixed $default
	 * @return string 
	 */
	public static function color_lookup($text, $default = null) {
		$colors = self::color_table();
		return avalue($colors, strtolower($text), $default);
	}
	
	/**
	 * Convert an RGB value to a hex value
	 * 
	 * @param array $rgb Array of three values between 0 and 255
	 * @param string $default
	 * @return string
	 */
	public static function rgb_to_hex($rgb, $default = null) {
		if (!is_array($rgb)) {
			return $default;
		}
		$color = "";
		foreach ($rgb as $c) {
			$c = clamp(0, $c, 255);
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
	 * @param mixed $default
	 * @return string
	 */
	public static function color_format($rgb, $default = null) {
		return '#' . self::rgb_to_hex($rgb, $default);
	}
	
	/**
	 * Parse a color and convert it to hexadecimal color
	 * 
	 * @param string $text Color value
	 * @param string $default
	 * @return string
	 */
	public static function color_normalize($text, $default = null) {
		$x = self::color_parse($text, $default);
		$x = self::rgb_to_hex($x, $default);
		return $x;
	}
	
	/**
	 * Does this string represent a CSS color value?
	 * 
	 * @param string $text
	 * @return boolean
	 */
	public static function is_color($text) {
		return self::color_parse($text, null) !== null;
	}
	
	/**
	 * Parse a color value from a CSS file
	 * 
	 * @todo Does not support rgba
	 * @param string $text
	 * @param string $default
	 * @return string
	 */
	public static function color_parse($text, $default = null) {
		$text = trim($text);
		if (strlen($text) == 0) {
			return $default;
		}
		$matches = false;
		if (preg_match('/^rgb\(([0-9,]+)\)$/', $text, $matches, false)) {
			$colors = explode(",", $matches[1]);
			if (count($colors) === 3) {
				foreach ($colors as $i => $c) {
					if ($c <= 0 || $c >= 255) {
						return $default;
					}
					$colors[$i] = intval($c);
				}
				return $colors;
			}
		}
		if ($text[0] == '#') {
			$text = substr($text, 1);
		} else {
			$text = self::color_lookup($text, $text);
		}
		$text_len = strlen($text);
		if ($text_len !== 3 && $text_len !== 6) {
			return $default;
		}
		$text = strtolower($text);
		if (!preg_match('/^[0-9a-z]+$/', $text)) {
			return $default;
		}
		$text_len = intval($text_len / 3);
		$result = array();
		//	echo "$text\n";
		//	echo "$text_len\n";
		for($i = 0; $i < 3; $i += 1) {
			$v = substr($text, $i * $text_len, $text_len);
			//		echo "$v\n";
			if ($text_len == 1)
				$v = $v . $v;
			$result[] = hexdec($v);
		}
		return $result;
	}
}
