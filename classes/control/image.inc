<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/control/image.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:32:12 EDT 2008
 */
class Control_Image extends Control_File {

	protected $theme = "control/file";

	function validate() {
		$image_src = $this->option("src", "");
		$image_thumb_src = $this->option("thumb_src", "");
		$this->DoToggle = false;
		if ($image_src != "") {
			$ff = $this->request->variables();
			$image_src = $this->object->apply_map($ff, $image_src);
			$image_thumb_src = $this->object->apply_map(map($image_thumb_src, $ff));
			if (!$this->has_option("dest_path")) {
				$this->set_option("dest_path", $image_src);
			}
			if (!empty($image_thumb_src)) {
				if (file_exists($image_src)) {
					$this->DoToggle = true;
				}
			}
		}
		return parent::validate();
	}

	function render() {
		$this->response->cdn_javascript('/share/zesk/js/zesk.js', array(
			'weight' => 'first'
		));
		$image_src = $this->option("src", "");
		$image_src = $this->object->apply_map(map($image_src, $this->request->variables()));
		$vi = new View_Image($this->options_include('image_host;is_relative;root_directory;ScaleWidth;ScaleHeight'));
		$vi_object['src'] = $image_src;
		$vi->set_option("src", $image_src);

		$path = $this->object->apply_map($this->option("dest_path", zesk::document_root() . $image_src));
		$name = $this->name();

		if (file_exists($path) && !is_dir($path)) {
			$result = "<div id=\"${name}_other\">" . $vi->output($vi_object) . "</div>" . toggle_edit("Change Image", $this->name(), parent::_output());
		} else {
			$result = parent::render();
		}
		return $result;
	}
}

