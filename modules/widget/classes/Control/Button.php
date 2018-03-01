<?php
/**
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * Created on Tue Jul 15 16:38:57 EDT 2008
 */
namespace zesk;

class Control_Button extends Control {
	
	/**
	 * Setting this converts the button into an A tag
	 * @param string $set
	 * @return Control_Button|string
	 */
	public function href($set = null) {
		return $set === null ? $this->option('href') : $this->set_option("href", $set);
	}
	public function button_label($set = null) {
		return $set === null ? $this->option('button_label') : $this->set_option("button_label", $set);
	}
	function submit() {
		if (($url = $this->option('redirect_url')) !== null) {
			$url = $this->object->apply_map($url);
			$url = URL::query_format($url, array(
				"ref" => $this->request->uri()
			));
			throw new Exception_Redirect($url, $this->object->apply_map($this->option('redirect_message')));
		}
		return true;
	}
	function theme_variables() {
		return parent::theme_variables() + array(
			'href' => $this->href(),
			'button_label' => $this->button_label()
		);
	}
}
