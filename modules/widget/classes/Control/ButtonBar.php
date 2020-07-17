<?php
/**
 * @package zesk
 * @subpackage control
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
namespace zesk;

class Control_ButtonBar extends Control {
	protected function initialize() {
		$spec = array();

		$locale = $this->application->locale;
		$ok_label = $this->option('label_ok', $locale->__('Save'));
		$cancel_label = $this->option('label_cancel', $locale->__('Cancel'));

		if ($ok_label) {
			$w = $this->widget_factory('Control_Button');
			$w->names('OK')->set_option('label_button', $ok_label);
			$w->class = "btn primary";
			$w->type = "cancel";
			$onclick = $this->option('ok_onclick', null);
			if ($onclick) {
				$w->set_option("submit", false);
				$w->set_option("onclick", $onclick);
			}
			$this->child($w);
		}
		if ($cancel_label) {
			$w = $this->widget_factory('Control_Button');
			$w->names('Cancel')->set_option('label_button', $cancel_label);
			$w->class = "btn";
			$w->type = "cancel";
			$onclick = $this->option('cancel_onclick', null);
			if ($onclick) {
				$w->set_option("submit", false);
				$w->set_option("onclick", $onclick);
			}
			$this->child($w);
		}
	}

	public function submitted() {
		return $this->request->has("OK") || $this->request->has("Cancel") || $this->request->is_post();
	}
}
