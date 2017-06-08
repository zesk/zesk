<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/View/Box.php $
 * @package zesk
 * @subpackage widgets
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 * Created on Sun Apr 04 21:26:38 EDT 2010 21:26:38
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class View_Box extends View {

	private $Content = null;

	static $stack = array();

	function initialize() {
		parent::initialize();
		$column = $this->column();
		if (!$column) {
			$column = "box-" . $this->response->id_counter();
			$this->column($column);
		}
	}
	function render() {
		$this->set_option('default', $this->Content, false);
		$attrs = array(
			"content" => $this->value()
		);
		$attrs = array_merge($attrs, $this->options_include("title;content;width;align;box_align;class;style;radius"));
		$attrs['box_style'] = $style = $this->option("box_style", "metal");
		$t = new Template($this->application, $this->option("template", "widgets/box/$style/Box.tpl"), $attrs);
		return $this->render_finish($t->render());
	}

	function _start() {
		ob_start();
	}

	function _cancel() {
		ob_end_clean();
	}

	function _end() {
		$content = strval(ob_get_clean());
		$model = new Model();
		$this->ready($model);
		$model->set($this->column(), $content);
		echo $this->execute($model);
	}

	public static function format($content, $style = "round") {
		$b = new View_Box(array(
			"box_style" => $style,
			"column" => "content"
		));
		$model = new Model();
		$model->content = $content;
		return $b->execute($model);
	}

	/**
	 * Start a box
	 *
	 * @param string $style Style, folder name found in widgets/box/STYLE
	 * @param array $attributes Other attributes to change features of the box (optional)
	 */
	public static function start($style, $attributes = false) {
		$attributes['box_style'] = $style;
		$attributes['column'] = "a";
		$b = new View_Box($attributes);
		$b->_start();
		array_push(self::$stack, $b);
	}

	public static function cancel() {
		$b = array_pop(self::$stack);
		if (!$b instanceof View_Box) {
			throw new Exception_Semantics("cancelling a box, but no box");
		}
		$b->_cancel();
		return true;
	}

	public static function end() {
		$b = array_pop(self::$stack);
		if (!$b instanceof View_Box) {
			throw new Exception_Semantics("box_end(): No boxes left? " . _backtrace());
		}
		$b->_end();
	}
}

/**
 * 
 * @param unknown $content
 * @param string $style
 * @return mixed|NULL|string
 */
function box($content, $style = "round") {
	zesk()->deprecated();
	return View_Box::format($content, $style);
}

/**
 * Start a box
 *
 * @param string $style Style, folder name found in widgets/box/STYLE
 * @param array $attributes Other attributes to change features of the box (optional)
 */
function box_start($style, $attributes = false) {
	zesk()->deprecated();
	return View_Box::start($style, $attributes);
}

/**
 * 
 * @return boolean
 */
function box_cancel() {
	zesk()->deprecated();
	return View_Box::cancel();
}

/**
 * 
 */
function box_end() {
	zesk()->deprecated();
	return View_Box::end();
}