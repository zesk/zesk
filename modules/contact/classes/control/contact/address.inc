<?php

class Control_Contact_Address extends Control_Edit {

	protected $class = "Contact_Address";

	protected $theme_widgets = "control/contact/address/widgets";

	protected function hook_widgets() {
		$ww = array();

		$ww[] = $w = Widget::factory('Control_Text')->names('street', __('Contact_Address:=Street Line 1'));
		$ww[] = $w = Widget::factory('Control_Text')->names('additional', __('Contact_Address:=Street Line 2'));
		$ww[] = $w = Widget::factory('Control_Text')->names('city', __('Contact_Address:=City'));
		$ww[] = $w = Widget::factory('Control_Text')->names('province', __('Contact_Address:=State'));
		$ww[] = $w = Widget::factory('Control_Text')->names('postal_code', __('Contact_Address:=Zip Code'));
		$ww[] = $w = Widget::factory('Control_County')->names('county', __('Contact_Address:=County'));
		$ww[] = $w = Widget::factory('Control_Country')->names('country', __('Contact_Address:=Country'));

		return $ww;
	}

	public function submit() {
		if (!$this->submit_handle_delete()) {
			return false;
		}
		if (!$this->submit_children()) {
			return false;
		}
		if ($this->parent && $this->object->member_is_empty('contact')) {
			$this->object->contact = $this->parent->object;
		}
		if (!$this->submit_store()) {
			return $this->hook_array("store_failed", array(), true);
		}
		return $this->submit_redirect();
	}
}
