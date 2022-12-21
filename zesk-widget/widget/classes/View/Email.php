<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

class View_Email extends View {
	public function render(): string {
		$value = $this->value();
		if (empty($value) || !is_email($value)) {
			return $this->empty_string();
		}
		$text = $this->option('format', '{' . $this->column() . '}');
		$text = $this->object->applyMap($text);
		$attrs = $this->options(toList('charset;coords;href;hreflang;rel;rev;shape;target;accesskey;class;dir;id;lang;style;tabindex;title;xml:lang'));
		$attrs['href'] = 'mailto:' . $value;
		return $this->render_finish(HTML::tag('a', $attrs, $text));
	}
}
