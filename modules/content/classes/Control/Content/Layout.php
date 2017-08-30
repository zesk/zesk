<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/content/classes/Control/Content/Layout.php $
 * @package zesk
 * @subpackage objects
 * @author kent
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Fri Sep 05 17:00:20 EDT 2008 17:00:20
 */
namespace zesk;

class Control_Content_Layout extends Control {
	private $Objects = array();
	private $Content = null;

	/**
	 *
	 * @var Content_Factory
	 */
	private $Content_Factory = null;
	const EQUALS_SEP = "--";
	function objects(array $objects = null) {
		if ($objects) {
			assert(is_array($objects));
			$this->Objects = $objects;
			return $this;
		}
		return $this->Objects;
	}
	function hook_content_factory(Content_Factory $factory = null) {
		return $this->content_factory($factory);
	}
	function content_factory(Content_Factory $factory = null) {
		if ($factory) {
			$this->Content_Factory = $factory;
			return $this;
		}
		return $this->Content_Factory;
	}
	private function _init_objects() {
		if (is_array($this->Objects))
			return true;

		$objects_field = $this->option("objects_field");
		if (!$objects_field) {
			return false;
		}

		$object = $this->object;
		
		$objects_string = $object->get($objects_field);
		if (!$objects_string) {
			return false;
		}

		$factory = $this->Content_Factory;
		if (!$factory instanceof Content_Factory) {
			$factory = $this->call_hook("content_factory");
			if (!$factory instanceof Content_Factory) {
				$factory = $this->parent()->call_hook("content_factory");
			}
		}

		$this->Objects = $this->Content_Factory->instantiate_content($objects_string);
		return true;
	}
	function load() {
		$object = $this->object;
		$name = $this->name();
		$layout_options = to_array(HTML::parse_attributes($object->get($name)));
		$object->set($name . "::Cols", clamp(1, to_integer(avalue($layout_options, "cols", 1)), 4));
		$object->set($name . "::Rows", clamp(1, to_integer(avalue($layout_options, "rows", 1)), 4));
		$object->set($name . "::Widths", avalue($layout_options, "widths", ""));
		$object->set($name . "::Objects", str_replace("=", self::EQUALS_SEP, avalue($layout_options, "objects", "")));
	}
	function validate() {
		$object = $this->object;
		$name = $this->name();
		$layout_options = array();
		foreach (to_list("Object;Widths") as $state) {
			$var = "${state}_${name}";
			$value = $this->request->get($var, $object->get($var));
			$object->set($var, $value);
			$layout_options[strtolower($state)] = $value;
		}
		$layout_options["objects"] = str_replace(self::EQUALS_SEP, "=", $layout_options["objects"]);
		$layout_options['cols'] = $object["Cols_$name"] = clamp(1, $this->request->_geti("Cols_$name", 1), 4);
		$layout_options['rows'] = $object["Rows_$name"] = clamp(1, $this->request->_geti("Rows_$name", 1), 4);

		$this->value($object, HTML::attributes($layout_options));
		return true;
	}
	function render() {
		$object = $this->object;
		$this->_init_objects();
		$name = $this->column();

		$this->response->jquery();
		$this->response->javascript("/share/zesk/widgets/layout/layout.js");
		$this->response->javascript("/share/zesk/jquery/ui/ui.core.js");
		$this->response->javascript("/share/zesk/jquery/ui/ui.draggable.js");
		$this->response->javascript("/share/zesk/jquery/ui/ui.droppable.js");
		$this->response->css("/share/zesk/widgets/layout/layout.css");

		$default_object_ids = array();
		$content = "";
		$n_objects = 0;
		foreach ($this->Objects as $object) {
			/* @var $object Object */
			if ($object->option_bool("control_layout_ignore")) {
				continue;
			}
			$n_objects++;
			$object_id = $object->option("menu_type") . self::EQUALS_SEP . $object->option("menu_id");
			$default_object_ids[] = $object_id;
			$object_name = $object->className() . ": " . $object->objectName();
			$content .= HTML::tag("div", "class=\"layout-object $name-object\" id=\"$object_id\"", $object_name);
		}
		$this->response->jquery("new Control_Layout('$name', $n_objects);");

		$content .= HTML::tag("div", array(
			"class" => "layout-grid",
			"id" => "$name-grid"
		), "");

		$content = HTML::tag("div", "class=\"layout\" id=\"$name-layout\"", $content);

		$content .= HTML::hidden("Objects_${name}", $object->get("Objects_${name}", implode(";", $default_object_ids)));
		$content .= HTML::hidden("Cols_${name}", $object->get("Cols_${name}", 1));
		$content .= HTML::hidden("Rows_${name}", $object->get("Rows_${name}", 1));
		$content .= HTML::hidden("Widths_${name}", $object->get("Widths_${name}", "1;1;2"));

		return $content;
	}
}

