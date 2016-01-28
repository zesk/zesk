<?php
/**
 * $URL: http://code.marketacumen.com/zesk/trunk/classes/u $
 * @package zesk
 * @subpackage default
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
class View_Layout extends View {

	/**
	 * @var array
	 */
	private $Objects = null;

	/**
	 * @var Content
	 */
	private $Content = null;

	/**
	 * @var Content_Factory
	 */
	private $Content_Factory = null;

	function objects($objects = null) {
		if (is_array($objects)) {
			$this->Objects = $objects;
			return $this;
		}
		return $this->Objects;
	}

	function content_factory(Content_Factory $factory = null) {
		if ($factory) {
			$this->Content_Factory = $factory;
			return $this;
		}
		return $this->Content_Factory;
	}

	private function _initObjects($data) {
		if (is_array($this->Objects)) {
			return true;
		}
		if (!$this->Content_Factory instanceof Content_Factory) {
			return false;
		}
		$objects_field = $this->option("objects_field");
		if (!$objects_field) {
			return false;
		}
		$objects_string = $data->get($objects_field);
		if (!$objects_string) {
			return false;
		}
		$this->Objects = $this->Content_Factory->instantiateContent($objects_string);
		return true;
	}

	function render(Model $data) {
		$layout = $this->value($data);
		if (empty($layout)) {
			return implode("\n", $this->generate_content());
		}
		$layout_options = (is_array($layout)) ? $layout : to_array(html::parse_attributes($layout), array());

		$rows = clamp(1, to_integer(avalue($layout_options, 'rows')), 4);
		$cols = clamp(1, to_integer(avalue($layout_options, 'cols')), 4);
		$widths = to_list(avalue($layout_options, 'widths'), array());
		$objects = explode("|", avalue($layout_options, 'objects', ''));

		if (count($widths) < $cols) {
			$widths[] = 1;
		}
		$wtot = 0;
		for($w = 0; $w < count($widths); $w++) {
			$wtot += intval($widths[$w]);
		}
		$wtot = max(1, $wtot);
		$ratio = 100 / $wtot;
		for($w = 0; $w < count($widths); $w++) {
			$widths[$w] = round($widths[$w] * $ratio, 0);
		}

		if ($cols + $rows > 2) {
			$all_prefix = "<table width=\"100%\" class=\"layout\">";
			$all_suffix = "</table>";
			$row_prefix = "<tr>";
			$row_suffix = "</tr>";
			$cell_prefix = '<td width="{width}%" valign="top"{class}>';
			$cell_suffix = '</td>';
		} else {
			$all_prefix = "";
			$all_suffix = "";
			$row_prefix = "";
			$row_suffix = "";
			$cell_prefix = "";
			$cell_suffix = "";
		}
		$cell_index = 0;
		$format = $all_prefix;
		for($row = 0; $row < $rows; $row++) {
			$format .= $row_prefix;
			for($col = 0; $col < $cols; $col++) {
				$width = intval($widths[$col]);
				$cell_attrs = array(
					"width" => $width
				);
				$width_class = ($width <= 35) ? "narrow" : (($width >= 65) ? "wide" : "medium");
				if ($col === 0) {
					$cell_attrs['class'] = " class=\"first $width_class\"";
				} else if ($col === $cols - 1) {
					$cell_attrs['class'] = " class=\"last $width_class\"";
				}
				$format .= map($cell_prefix, $cell_attrs);
				$object_cell = avalue($objects, $cell_index);
				if (!empty($object_cell)) {
					$format .= '{' . implode('}{', explode(";", $object_cell)) . '}';
				}
				$cell_index++;
				$format .= $cell_suffix;
			}
			$format .= $row_suffix;
		}
		$format .= $all_suffix;

		$this->_initObjects($data);
		$contents = $this->generate_content();

		return map($format, $contents);
	}

	private function generate_content() {
		if (is_array($this->Content)) {
			return $this->Content;
		}
		$layout_content = array();
		foreach ($this->Objects as $object) {
			$content = null;
			if ($object instanceof Object) {
				/* @var $object Object */
				$content = $object->output();
			} else if ($object instanceof Template) {
				$object->set($this->option());
				$content = $object->output();
			} else {
				$object = null;
			}
			if ($object) {
				$object_name = $object->get("menu_type") . "=" . $object->get("menu_id");
				$layout_content[$object_name] = $content;
			}
		}
		$this->Content = $layout_content;
		return $this->Content;
	}
}

