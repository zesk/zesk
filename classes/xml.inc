<?php
/**
 * 
 */

class xml {
	/**
	 * Return array of XML_Nodes or a single XML_Node depending on the content
	 * 
	 * @param unknown $xml
	 * @throws Exception_Syntax
	 * @throws Exception_Semantics
	 * @return XML_Node|array
	 */
	public static function parse($xml) {
		$xml_values = null;
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, trim($xml), $xml_values);
		$exception = null;
		if (!$xml_values) {
			$exception = new Exception_Syntax("XML parsing failed: {content}", array(
				"content" => $xml
			));
		}
		xml_parser_free($parser);
		if ($exception) {
			throw $exception;
		}
		$root = $top = new XML_Node('');
		$stack = array(
			$top
		);
		foreach ($xml_values as $item) {
			$tag = $type = $level = $attributes = $value = null;
			extract($item, EXTR_IF_EXISTS);
			if ($type === "close") {
				if ($top->name() !== $tag) {
					throw new Exception_Semantics("Close tag {tag} does not match open tag {open_tag}", array(
						"tag" => $tag, 
						"open_tag" => $top->name()
					));
				}
				array_pop($stack);
				$top = $stack[count($stack) - 1];
				continue;
			}
			if ($type === "open") {
				$node = new XML_Node($tag, $attributes);
				$top->add($node);
				$stack[] = $top = $node;
			} else if ($type === "complete") {
				$node = new XML_Node($tag, $attributes, $value);
				$top->add($node);
			}
		}
		$children = $root->children();
		if (count($children) === 1) {
			return $children[0];
		}
		return $children;
	}
}
