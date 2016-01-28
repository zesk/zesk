<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/control/edit.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 *            Created on Tue Jul 15 16:28:30 EDT 2008
 */

/**
 * Edit an object with multiple columns
 *
 * @author kent
 * @see Control
 * @see Widget::execute
 */
class Control_Edit extends Control {

	const option_duplicate_message = 'duplicate_message';

	/**
	 * Force type here
	 *
	 * @var Object
	 */
	protected $object = null;

	/**
	 * Class of the object we're listing.
	 *
	 * @var string
	 */
	protected $class = null;

	/**
	 * Options to create the object we're listing, per row
	 *
	 * @var string
	 */
	protected $class_options = null;

	/**
	 * Header theme
	 *
	 * @var string
	 */
	protected $theme_prefix = null;

	/**
	 * Header theme
	 *
	 * @var string
	 */
	protected $theme_header = null;

	/**
	 * Row theme
	 *
	 * @var string
	 */
	protected $theme_row = null;

	/**
	 * Layout theme with replacement variables for widget renderings
	 *
	 * @var string
	 */
	protected $theme_widgets = null;

	/**
	 * Footer theme
	 *
	 * @var string
	 */
	protected $theme_footer = null;
	/**
	 * Suffix theme
	 *
	 * @var string
	 */
	protected $theme_suffix = null;

	/**
	 * Row tag
	 */
	protected $form_tag = "form";
	/**
	 * Row attributes
	 *
	 * @var array
	 */
	protected $form_attributes = array(
		"class" => "edit",
		"method" => "post",
		"role" => "form"
	);

	/**
	 *
	 * @var array
	 */
	protected $widgets = array();

	/**
	 * Cell tag
	 *
	 * @var array
	 */
	protected $widget_tag = "div";

	/**
	 * Cell attributes
	 *
	 * @var array
	 */
	protected $widget_attributes = array(
		"class" => "form-group"
	);

	/**
	 * Label attributes
	 *
	 * @var unknown
	 */
	protected $label_attributes = array();

	/**
	 * String
	 *
	 * @var string tag to wrap each widget output with
	 */
	protected $widget_wrap_tag = null;

	/**
	 * Optional wrap attributes for each widget
	 *
	 * @var array
	 */
	protected $widget_wrap_attributes = array();

	/**
	 * Fields to preserve in the form from the request
	 *
	 * @var array
	 */
	protected $form_preserve_hidden = array(
		"ajax",
		"ref"
	);

	/**
	 * Lazy evaluate the class based on this object's class name (if not set)
	 *
	 * @return string
	 */
	private function _class() {
		if ($this->class === null) {
			$this->class = str::unprefix(get_class($this), __CLASS__ . '_');
		}
		return $this->class;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::model()
	 */
	function model() {
		return Object::factory($this->_class());
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::initialize($object)
	 */
	protected function initialize() {
		if (!$this->name()) {
			$this->name("edit");
		}
		$ww = $this->hook('widgets');
		$filter = $this->option_list('widgets_filter', null);
		if (count($filter) > 0) {
			foreach ($ww as $i => $w) {
				if (!in_array($w->column(), $filter)) {
					unset($ww[$i]);
				}
			}
		}
		$this->children($ww);

		parent::initialize();

		$this->initialize_theme_paths();

		$this->form_attributes['action'] = $this->request->path();

		$this->form_attributes = html::add_class($this->form_attributes, strtr(strtolower(get_class($this)), "_", '-'));
		if ($this->parent && $this->traverse === null) {
			$this->traverse = true;
			if ($this->parent instanceof Control_Edit) {
				$this->form_tag = null;
			}
		}
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::validate()
	 */
	public function validate() {
		if ($this->request->get('delete') !== null && $this->user_can("delete", $this->object)) {
			return true;
		}
		return parent::validate();
	}

	/**
	 *
	 * @return boolean
	 */
	protected function delete_redirect() {
		$redirect = avalue($this->options, "delete_redirect");
		$vars = arr::kprefix($this->object->variables(), "object.") + arr::kprefix($this->request->variables(), "request.");
		$url = null;
		if ($redirect) {
			$redirect = map($redirect, $vars);
			$url = url::query_format($redirect, "ref", $this->request->get("ref", $this->request->url()));
		}
		$message = map($this->option('delete_redirect_message'), $vars);
		if ($this->request->is_ajax()) {
			$this->response->json(array(
				"result" => true,
				"message" => $message,
				"redirect" => $url,
				"object" => $this->object->json(array(
					'depth' => 0
				))
			));
			// Stop processing submit
			return false;
		}
		if (!$redirect) {
			return true;
		}
		$this->response->redirect($url, $message);
		// Stop processing submit
		return false;
	}

	public function duplicate_message($set = null) {
		return $set === null ? $this->option(self::option_duplicate_message) : $this->set_option(self::option_duplicate_message, $set);
	}

	private function _get_duplicate_message() {
		$message = $this->option(self::option_duplicate_message, 'Another {Class_Object::name} with the same name already exists.');
		$message = $this->hook_array("duplicate_message", array(
			$message
		), $message);
		$message = __($message, $this->object->variables());
		return $message;
	}
	public function submit_store() {
		try {
			if (!$this->object->store()) {
				$this->error($this->option('store_error', __('Unable to save {object}', array(
					'object' => strval($this->object)
				))));
				$this->hook('store_failed');
				return true;
			}
			$this->hook('stored');
		} catch (Database_Exception_Duplicate $dup) {
			$this->error($this->_get_duplicate_message());
			return $this->hook_array("store_failed", array(), false);
		} catch (Exception_Object_Duplicate $dup) {
			$this->error($this->_get_duplicate_message());
			return $this->hook_array("store_failed", array(), false);
		}
		return true;
	}
	protected function submit_handle_delete() {
		if ($this->request->get('delete') && $this->user_can("delete", $this->object)) {
			$this->object->delete();
			return $this->delete_redirect();
		}
		// Continue
		return true;
	}
	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::submit()
	 */
	public function submit() {
		if (!$this->submit_handle_delete()) {
			return true;
		}
		if (!$this->submit_children()) {
			return true;
		}
		if (!$this->submit_store()) {
			return true;
		}
		return $this->submit_redirect();
	}

	/**
	 * Set up theme paths
	 *
	 * @return void
	 */
	protected function initialize_theme_paths() {
		$hierarchy = zesk::class_hierarchy($this, __CLASS__);
		foreach ($hierarchy as $index => $class) {
			$hierarchy[$index] = strtr(strtolower($class), "_", "/") . "/";
		}
		// Set default theme to control/foo/prefix, control/foo/header etc.
		foreach (to_list("prefix;header;footer;suffix") as $var) {
			$theme_var = "theme_$var";
			if (!$this->$theme_var) {
				$this->$theme_var = arr::suffix($hierarchy, $var);
			}
		}
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Widget::theme_variables()
	 */
	public function theme_variables() {
		$enctype = avalue($this->form_attributes, 'enctype');
		if ($enctype === null && $this->upload()) {
			$this->form_attributes['enctype'] = 'multipart/form-data';
		}
		return array(
			'widgets' => $this->children(),
			'theme_prefix' => $this->theme_prefix,
			'theme_suffix' => $this->theme_suffix,
			'theme_header' => $this->theme_header,
			'theme_row' => $this->theme_row,
			'theme_footer' => $this->theme_footer,
			'form_tag' => $this->form_tag,
			'form_attributes' => $this->form_attributes,
			'label_attributes' => $this->label_attributes,
			'widget_tag' => $this->widget_tag,
			'widget_attributes' => $this->widget_attributes,
			'widget_wrap_tag' => $this->widget_wrap_tag,
			'widget_wrap_attributes' => $this->widget_wrap_attributes,
			'nolabel_widget_wrap_attributes' => firstarg($this->nolabel_widget_wrap_attributes, $this->widget_wrap_attributes),
			'form_preserve_hidden' => $this->form_preserve_hidden,
			'theme_widgets' => $this->theme_widgets,
			'title' => $this->title()
		) + parent::theme_variables() + $this->options;
	}
}

