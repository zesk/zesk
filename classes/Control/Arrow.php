<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Control/Arrow.php $
 * @package zesk
 * @subpackage control
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Control_Arrow extends Control {

	static $id_index = 0;

	static $stack = array();

	private function _open() {
		$is_open = $this->option_bool('is_open');
		$id = $this->option('id');
		if (!$id) {
			$id = 'control_arrow_' . self::$id_index;
			self::$id_index++;
		}
		$url = $this->option("url");

		$js = "javascript:Control_Arrow_toggle('$id','$url')";

		$img_attrs = $this->option_array("img_attrs", array());
		$img_url = $this->option('img_url', $this->option('img_url', cdn::url("/share/zesk/images/toggle/small-{state}.gif")));
		$img_attrs['src'] = map($img_url, array(
			'state' => ($is_open ? "down" : "right")
		));
		$img_attrs['data-src-open'] = map($img_url, array(
			'state' => "down"
		));
		$img_attrs['data-src-closed'] = map($img_url, array(
			'state' => "right"
		));

		if (!array_key_exists('alt', $img_attrs)) {
			$img_attrs['alt'] = '';
		}
		$img_attrs['id'] = $id . "_img";

		$img = HTML::tag("img", $img_attrs, null);

		$label_tag = $this->option("label_tag", "h2");
		$label_attrs = $this->option_array("label_attrs", array());

		$label_a_attrs = $this->option_array("label_a_attrs", array());
		$label_a_attrs['href'] = $js;

		$contents_attrs = $this->option_array("contents_attrs", array());
		$contents_attrs['id'] = $id;
		$contents_attrs['style'] = "display: " . ($is_open ? "block" : "none");
		if (!array_key_exists('class', $contents_attrs)) {
			$contents_attrs['class'] = 'toggle-arrow-content';
		}

		$inner_link = HTML::tag("a", $label_a_attrs, $this->option('title'));

		if ($label_tag) {
			$inner_link = HTML::tag($label_tag, $label_attrs, $inner_link);
		}

		return HTML::tag("div", array(
			"class" => "toggle-arrow"
		), HTML::tag("a", array(
			"class" => "toggle-arrow",
			"href" => $js
		), $img) . $inner_link) . HTML::tag_open("div", $contents_attrs);
	}

	private function _close() {
		return HTML::tag_close('div');
	}

	public static function html_init() {
		Response::instance()->cdn_javascript("/share/zesk/js/zesk.js", array('weight' => 'first'));
		Response::instance()->jquery("Control_Arrow_onload();");
	}

	function render() {
		self::html_init();

		$value = $this->value();

		$contents = $this->_open() . $value . $this->_close();

		return $this->render_finish($contents);
	}

	public static function html($title, $is_open = false, $options = null, $content = "") {
		return self::open($title, $is_open, $options) . $content . self::close();
	}

	public static function open($title, $is_open = false, $options = null) {
		$options['title'] = $title;
		$options['is_open'] = to_bool($is_open);
		$widget = new Control_Arrow($options);
		$widget->set_option('column', 'data');
		self::$stack[] = $widget;
		ob_start();
		return "";
	}

	public static function open_set_option($name, $value = null) {
		$widget = avalue(self::$stack, count(self::$stack) - 1);
		if (!$widget) {
			throw new Exception_Semantics("Control_Arrow::open_option with no open");
		}
		$widget->set_option($name, $value);
	}

	public static function close() {
		$widget = array_pop(self::$stack);
		if (!$widget) {
			throw new Exception_Semantics("Control_Arrow::close with no open");
		}
		$object = new Model();
		$object->data = ob_get_clean();
		return $widget->execute($object);
	}
}

