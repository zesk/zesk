<?php
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Control_Contact_Address extends Control_Edit {
	/**
	 *
	 * @var string
	 */
	protected $class = "zesk\Contact_Address";

	/**
	 *
	 * @var string
	 */
	protected $theme_widgets = "zesk/control/contact/address/widgets";

	/**
	 *
	 * @return \zesk\Widget[]
	 */
	protected function hook_widgets() {
		$ww = array();

		$ww[] = $w = $this->widget_factory('zesk\\Control_Text')->names('street', __('Contact_Address:=Street Line 1'));
		$ww[] = $w = $this->widget_factory('zesk\\Control_Text')->names('additional', __('Contact_Address:=Street Line 2'));
		$ww[] = $w = $this->widget_factory('zesk\\Control_Text')->names('city', __('Contact_Address:=City'));
		$ww[] = $w = $this->widget_factory('zesk\\Control_Text')->names('province', __('Contact_Address:=State'));
		$ww[] = $w = $this->widget_factory('zesk\\Control_Text')->names('postal_code', __('Contact_Address:=Zip Code'));
		$ww[] = $w = $this->widget_factory('zesk\\Control_County')->names('county', __('Contact_Address:=County'));
		$ww[] = $w = $this->widget_factory('zesk\\Control_Country')->names('country', __('Contact_Address:=Country'));

		return $ww;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Control_Edit::submit()
	 */
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
			return $this->call_hook_arguments("store_failed", array(), true);
		}
		return $this->submit_redirect();
	}
}
