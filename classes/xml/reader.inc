<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/xml/reader.inc $
 * @package zesk
 * @subpackage tools
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */

/**
 * XMLReader
 *
 * Long description
 *
 * @package zesk
 * @subpackage tools
 */
abstract class XML_Reader extends SimpleXMLElement {
	function attributes() {
		return new XML_Attributes(parent::attributes());
	}
	
	function requireAttributes($mixed) {
		if (is_string($mixed)) {
			$mixed = explode(";", $mixed);
		}
		if (!is_array($mixed)) {
			return $this->attributes();
		}
		$attr = $this->attributes();
		foreach ($mixed as $k => $n) {
			if ($attr->has_option($n, true)) {
				unset($mixed[$k]);
			}
		}
		if (count($mixed) == 0) {
			return $attr;
		}
		throw new Exception_Semantics(htmlspecialchars(substr($this->asXML(), 0, 200)) . "...: the attributes " . implode(", ", $mixed) . " are required");
	}
}

// From http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss/
// $parser = xml_parser_create("");
// xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
