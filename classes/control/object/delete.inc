<?php
/**
 * $URL: http://code.marketacumen.com/zesk/trunk/classes/control/object/edit.inc $
 * @package zesk
 * @subpackage widgets
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2006, Market Acumen, Inc.
 * @deprecated 2014-06-11
 */

zesk::obsolete();

class Control_Object_Delete extends Control_Object {

	/**
	 * The class name of the object to create (override in child classes)
	 * @var string
	 */
	protected $class = null;

	/**
	 * Instance of object created with this control
	 * @var Object
	 */
	protected $object = null;

	/**
	 * Initialize the object with this data (optional), override in child classes
	 * @var mixed
	 */
	protected $object_data = null;

	/**
	 * Initialize the object with this options (optional), override in child classes
	 * @var mixed //
	 */
	protected $object_options = null;

	function __construct($options = false) {
		parent::__construct($options);
		$this->WidgetListSet = false;
	}

	function object() {
		return $this->object;
	}

	function _widgets_new() {
		return $this->n;
	}

	private function permission_error_url() {
		return $this->option("permission_error_url", _G('ControlObjectForm::permission_error_url_default', "/admin/index.php"));
	}

	private function _init_new($object) {
		if (!$this->user_can($object, "new")) {
			$this->response->redirect_default($this->permission_error_url(), __("You do not have permission to create new {class_name-context-object-plural}.", $object->objectMap()));
			return false;
		}
		$className = $object->className();
		$this->Action = "store";
		$title = __("ControlObjectForm:=Add {0}", __($className));
		html::title($title, false);
		$button = __("ControlObjectForm:=Create {0}", __($className));
		$this->set_option("OKButton", $button, false);
		return true;
	}

	private function _init_edit($object) {
		if (!$this->user_can($object, $this->option('default_action', "edit"))) {
			$this->response->redirect_default($this->permission_error_url(), __("You do not have permission to edit {class_name-context-object-plural}.", $object->objectMap()));
			return false;
		}
		$className = $object->className();
		$this->Action = "store";
		$title = __("ControlObjectForm:=Update {0}", __($className));
		html::title($title, false);
		$this->set_option("OKButton", $title, false);
		return true;
	}

	function _widgets() {
		$object = $this->object;
		if ($object->is_new()) {
			$this->_init_new($object);
			$method = $this->option("new_widget_list_method", "newWidgetList");
			$widgets = $object->$method();
		} else {
			$this->_init_edit($object);
			$method = $this->option("edit_widget_list_method", "editWidgetList");
			$widgets = $object->$method();
		}
		return $widgets;
	}

	function model() {
		return Object::factory($this->class, $this->object_data, $this->object_options);
	}

	function load() {
		$object = $this->object;
		/* @var $object Object */
		$id = $this->request->get($this->option('id_name', $object->id_column()), null);
		if ($object->is_new() && !empty($id)) {
			$object->initialize($id);
		}
		$object->fetch();
		parent::load();
	}

	function validate() {
		/* @var $object Object */
		$action = strtolower($this->request->get("action", ""));
		$is_continue = $this->request->getb("_continue");

		if ($action === "delete" && !$is_continue) {
			$this->Action = "delete";
			return $this->widgetActionDelete();
		}
		$is_new = $this->object->is_new();
		if (!$is_continue) {
			if ($this->request->get("Cancel", "") != "") {
				$cancelMessage = $this->option("cancel_message", __("No changes were made to the {class_name-context-object-singular}."));
				$cancelNewMessage = $this->option("cancel_new_message", __("No {class_name-context-subject-singular} was created."));
				$this->Action = "cancel";
				$this->_finish($is_new ? $cancelNewMessage : $cancelMessage);
				return true;
			}
		}

		$result = parent::validate();
		$_continue = $this->request->getb("_continue");
		if (strlen($_continue) > 0) {
			$this->clearErrors();
			$this->message("continue", "Continuing");
			return false;
		}
		return $result ? !$this->hasErrors() : false;
	}

	function isCancel() {
		return $this->Action == "cancel";
	}

	function isDelete() {
		return $this->Action == "delete";
	}

	function isStore() {
		return $this->Action == "store";
	}

	function submit() {
		/* @var $object Object */
		if ($this->Action == "cancel") {
			return;
		}
		$object = $this->object;
		$is_new = $object->is_new();

		$store_error = false;
		if ($this->before_store()) {
			if ($object->store()) {
				$this->after_store(true);
				parent::submit();
				$default_message = !$is_new ? __('ControlObjectForm:={class_name-context-subject-singular} "{display_name}" was updated.') : __('ControlObjectForm:={class_name-context-subject-singular} "{display_name}" was added.');
				$store_message = $this->option("store_message", $default_message);
				if ($is_new) {
					$store_message = $this->option("store_new_message", $store_message);
				}
				$message = $object->apply_map($store_message, array(
					"display_name" => $object->display_name()
				));
				$this->_finish($message);
				return;
			}
			$this->after_store(false);
		}
		$name = strtolower($object->className());
		if ($object->has_option("store_error")) {
			$store_error = $object->option("store_error");
			if (is_array($store_error)) {
				$this->error($store_error);
			} else {
				$this->setError("store", $store_error);
			}
		} else {
			$this->setError("store", $this->option("store_error", lang::indefinite_article($name, true) . " with that name already exists."));
		}
	}

	function render() {
		$this->page_configure();

		/* @var $object Object */
		$attrs = array();

		$attrs["action"] = $this->option("action", url::current_uri());
		$attrs["method"] = $this->option("method", "post");
		$is_upload = $this->option("FileUpload");
		$prefix = "";
		$form_suffix = "";
		$suffix = "";
		$attrs["name"] = "object_form";
		$attrs["id"] = $attrs["name"];
		$attrs["onsubmit"] = $this->option("onsubmit");

		if ($this->upload() || $is_upload) {
			$attrs["enctype"] = "multipart/form-data";
		}
		$this->form_name($attrs['name']);

		$template_variables = $this->template_variables();
		$form_data = $this->load_template($template_variables);
		if ($form_data === null) {
			$form_data = '{ErrorString}';
			if ($this->option_bool('button_bar_top', true)) {
				$form_data .= '{ButtonBar}';
			}
			$form_data .= parent::_output($template_variables);
			if ($this->option_bool('button_bar_bottom', true)) {
				$form_data .= '{ButtonBar}';
			}
		}
		if (!$this->option_bool("no_form")) {
			$form_data = html::tag("form", $attrs, $form_data);
		}
		if (!$this->option_bool('skip_map')) {
			$form_data = map($form_data, $template_variables);
			if (strpos($form_data, '{') !== false) {
				$form_data = map($form_data, $template_variables);
			}
		}
		$result = $prefix . $form_data . $suffix . $form_suffix;
		return $this->render_finish($result);
	}
}

