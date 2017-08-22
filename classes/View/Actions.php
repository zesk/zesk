<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/View/Actions.php $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jun 17 00:58:51 EDT 2008
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class View_Actions extends View {
	protected function initialize() {
		parent::initialize();
		$this->set_option(array(
			"list_order_by" => false,
			"class" => "view-actions",
			"label" => "Actions"
		), false, false);
	}
	private function actionlink($href, $src, $w, $h, $title, $confirm = false, $use_cdn = false) {
		$x = $this->object;
		$attr = $confirm ? array(
			"onclick" => "return confirm('Are you sure?')"
		) : array();

		$image = $use_cdn ? HTML::img($this->application, $src, $x->apply_map($title), array(
			"width" => $w,
			"height" => $h
		)) : HTML::img($this->application, $src, $x->apply_map($title), array(
			"width" => $w,
			"height" => $h
		));
		return HTML::a($x->apply_map($href), $attr, $image);
	}
	private function _action_href($action, $add_ref = true) {
		$object = $this->object;
		if ($this->has_option($action . "_href")) {
			return $object->apply_map($this->option($action . '_href'));
		}
		$hr = $this->application->router()->get_route($action, $object, $this->option_array("route_options"));
		if ($add_ref) {
			$hr = URL::query_format($hr, array(
				'ref' => $this->request->uri()
			));
		}
		return $hr;
	}
	function format($set = null) {
		return ($set !== null) ? $this->set_option('format', $set) : $this->option('format');
	}
	function action_add($url, $text, $src, array $options = array()) {
		$options['href'] = $url;
		$options['text'] = $text;
		$options['src'] = $src;
		$actions = to_array(avalue($this->options, 'actions', array()));
		$actions[] = $options;
		$this->options['actions'] = $actions;
		return $this;
	}
	function render() {
		$html = "";

		if ($this->option("add_div", true)) {
			$html = $html . "<div class=\"list-actions\">";
		}

		$format = $this->option("format", "{Name}");

		if ($this->option("show_edit", true)) {
			$url = $this->_action_href("edit");
			if ($url) {
				$html .= $this->actionlink($url, "/share/zesk/images/actions/edit.gif", 18, 18, "Edit \"$format\"", false, true);
			}
		}

		if ($this->option("show_delete", true)) {
			$url = $this->_action_href("delete");
			if ($url) {
				$html .= $this->actionlink($url, "/share/zesk/images/actions/delete.gif", 18, 18, "Delete \"$format\"", true, true);
			}
		}

		$actions = $this->option_array("actions");
		if (is_array($actions)) {
			foreach ($actions as $actSpec) {
				if (is_array($actSpec)) {
					$actSpec = $this->object->apply_map($actSpec);
					$hr = avalue($actSpec, "href", "");
					$src = avalue($actSpec, "src", "");
					$text = avalue($actSpec, "text", "");
					$add_ref = avalue($actSpec, 'add_ref', false);
					if ($add_ref) {
						$hr = URL::query_format($hr, array(
							'ref' => $this->request->uri()
						));
					}
					$html = $html . $this->actionlink($hr, avalue($actSpec, "prefix", "") . $src, 18, 18, $text, avalue($actSpec, "confirm", false, avalue($actSpec, 'cdn')));
				}
			}
		}

		if ($this->option("add_div", true)) {
			$html = $html . "</div>";
		}
		return $html;
	}
}

