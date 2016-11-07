<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/view/email.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 */
class View_Email extends View {

	function render() {
		$value = $this->value();
		if (empty($value) || !is_email($value)) {
			return $this->empty_string();
		}
		$text = $this->option("format", '{' . $this->column() . '}');
		$text = $this->object->apply_map($text);
		$attrs = $this->options_include('charset;coords;href;hreflang;rel;rev;shape;target;accesskey;class;dir;id;lang;style;tabindex;title;xml:lang');
		$attrs['href'] = 'mailto:' . $value;
		return $this->render_finish(HTML::tag('a', $attrs, $text));
	}
}

