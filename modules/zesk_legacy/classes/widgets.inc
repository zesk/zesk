<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/zesk_legacy/classes/widgets.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 *            Created on Sat Apr 03 20:33:30 EDT 2010 20:33:30
 */

/**
 * Factory class for widgets.
 * @author kent
 * @deprecated 2014 use Widget::factory() chains
 */
class widgets {

	/**
	 * Create a new action widget
	 * @param string $format Format of the object name based on the row
	 * @return View_Actions
	 */
	public static function actions($format = "{Name}", $url_prefix = false) {
		zesk::deprecated();
		$w = new View_Actions();
		$w->names('actions');
		$w->set_option("format", $format);
		$w->set_option("list_order_by", false);
		if ($url_prefix) {
			$w->set_option("url_prefix", $url_prefix);
		}
		return $w;
	}

	/**
	 * Create a new Control widget for text input
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @param integer $minLength The minimum required length of text required in this widget. Specify negative numbers
	 *        for no minimum.
	 * @param integer $maxLength The maximum allowed length of text required in this widget. Specify negative numbers
	 *        for no maximum.
	 * @return Control_Text
	 */
	public static function control_text($name, $label = false, $required = false, $minLength = -1, $maxLength = -1) {
		zesk::deprecated();
		$w = new Control_Text();
		$w->names($name, $label, "");
		$w->required($required);
		$w->set_option("minlength", $minLength);
		$w->set_option("maxlength", $maxLength);
		return $w;
	}
	public static function control_url($name, $label = false, $required = false, $minLength = -1, $maxLength = -1) {
		zesk::deprecated();
		$w = new Control_URL();
		$w->names($name, $label, "");
		$w->required($required);
		$w->set_option("minlength", $minLength);
		$w->set_option("maxlength", $maxLength);
		return $w;
	}

	/**
	 * Create a new Control_ widget which accepts an integer.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @param integer $minValue The minimum value allowed or false if any minimum allowed. Clamps value.
	 * @param integer $maxValue The maximum value allowed or false if any maximum allowed. Clamps value.
	 * @return Control_Text
	 */
	public static function control_integer($name, $label = false, $required = false, $minValue = false, $maxValue = false) {
		zesk::deprecated();
		$w = self::control_text($name, $label, $required, 1, 11);
		$w->set_option("validate", "integer");
		$w->set_option("show_size", 8);
		if ($minValue != false) {
			$w->set_option("minValue", $minValue);
		}
		if ($maxValue != false) {
			$w->set_option("maxValue", $maxValue);
		}
		return $w;
	}

	/**
	 * Create a new Control_ widget which accepts a real value
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @param integer $minValue The minimum value allowed or false if any minimum allowed. Clamps value.
	 * @param integer $maxValue The maximum value allowed or false if any maximum allowed. Clamps value.
	 * @return Control_Text
	 */
	public static function control_real($name, $label = false, $required = false, $minValue = false, $maxValue = false) {
		zesk::deprecated();
		$w = self::control_text($name, $label, $required, 1, 11);
		$w->set_option("validate", "real");
		if ($minValue != false) {
			$w->set_option("minValue", $minValue);
		}
		if ($maxValue != false) {
			$w->set_option("maxValue", $maxValue);
		}
		return $w;
	}

	/**
	 * Create a new Control_ widget which accepts free-form text.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @param integer $minLength The minimum required length of text required in this widget. Specify negative numbers
	 *        for no minimum.
	 * @param integer $maxLength The maximum allowed length of text required in this widget. Specify negative numbers
	 *        for no maximum.
	 * @return Control_Text
	 */
	public static function control_textarea($name, $label = false, $required = false, $minLength = -1, $maxLength = -1) {
		zesk::deprecated();
		$w = self::control_text($name, $label, $required, $minLength, $maxLength);
		$w->set_option("textarea", true);
		return $w;
	}

	/**
	 * Create a new Control_ widget which accepts free-form text.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @param integer $minLength The minimum required length of text required in this widget. Specify negative numbers
	 *        for no minimum.
	 * @param integer $maxLength The maximum allowed length of text required in this widget. Specify negative numbers
	 *        for no maximum.
	 * @return Control_Text
	 */
	public static function control_ip_list($name, $label = false, $required = false, $allow_masks = false) {
		zesk::deprecated();
		$w = new Control_IP_List(array(
			"allow_ip_masks" => $allow_masks
		));
		$w->names($name, $label);
		$w->required($required);
		return $w;
	}
	public static function control_IP($name, $label = false, $required = false) {
		zesk::deprecated();
		$w = new Control_IP();
		$w->names($name, $label);
		$w->required($required);
		return $w;
	}

	/**
	 * Create a new Control_ widget which is a HTML editor
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @param integer $minLength The minimum required length of text required in this widget. Specify negative numbers
	 *        for no minimum.
	 * @param integer $maxLength The maximum allowed length of text required in this widget. Specify negative numbers
	 *        for no maximum.
	 * @return Control_Text
	 */
	public static function control_richtext($name, $label = false, $required = false, $minLength = -1, $maxLength = -1) {
		zesk::deprecated();
		$w = new Control_RichText();
		$w->names($name, $label, "");
		$w->required($required);
		$w->set_option("length_minimum", $minLength);
		$w->set_option("length_maximum", $maxLength);
		return $w;
	}

	/**
	 * Create a new Control_ widget which accepts a valid email address.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @param integer $minLength The minimum required length of text required in this widget. Specify negative numbers
	 *        for no minimum.
	 * @param integer $maxLength The maximum allowed length of text required in this widget. Specify negative numbers
	 *        for no maximum.
	 * @return Control_Text
	 */
	public static function control_email($name, $label = false, $required = false) {
		zesk::deprecated();
		$w = new Control_Email();
		$w->names($name, $label, "");
		$w->required($required);
		return $w;
	}

	/**
	 * Create a new Control_ widget which accepts a phone number.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @param integer $minLength The minimum required length of text required in this widget. Specify negative numbers
	 *        for no minimum.
	 * @param integer $maxLength The maximum allowed length of text required in this widget. Specify negative numbers
	 *        for no maximum.
	 * @return Control_Phone
	 */
	public static function control_phone($name, $label = false, $required = false, $minLength = 10, $maxLength = 16) {
		zesk::deprecated();
		$w = new Control_Phone();
		$w->names($name, $label, "");
		$w->required($required);
		$w->set_option("length_minimum", $minLength);
		$w->set_option("length_maximum", $maxLength);
		return $w;
	}

	/**
	 * Create a new Control_ widget which accepts a password.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @param boolean $confirm Set to true to require the user to enter the password twice for confirmation.
	 * @return Control_Password
	 */
	public static function control_password($name, $label = false, $required = false, $confirm = false) {
		zesk::deprecated();
		$w = new Control_Password();
		$w->names($name, $label, "");
		$w->required($required);
		$w->set_option("confirm", $confirm);
		$w->set_option("minlength", _G("password_min_length", 6));
		return $w;
	}

	/**
	 * Create a new Control_ widget which is a button.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param string $button_label The button label.
	 * @return Control_Button
	 */
	public static function control_button($name, $label = false, $button_label = false) {
		zesk::deprecated();
		$w = new Control_Button();
		$w->names($name, $label, "");
		if ($label === "") {
			$w->set_option("nolabel", true);
		}
		if (is_string($button_label)) {
			$w->set_option("button_label", $button_label);
		}
		return $w;
	}

	/**
	 * Create a new Control_ widget which is a date.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @return Control_Date
	 */
	// 	public static function control_date($name, $label = false, $required = false) {
	// 		$w = new Control_Date();
	// 		$w->names($name, $label, "");
	// 		$w->required($required);
	// 		return $w;
	// 	}


	/**
	 * Create a new Control_ widget which is a date range selector.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @return Control_DateRange
	 */
	// 	public static function control_date_range($name, $label = false, $required = false, $new = false) {
	// 		$w = null;
	// 		if ($new === 3) {
	// 			$w = new Control_Date_Range3();
	// 		} else if ($new) {
	// 			$w = new Control_Date_Range2();
	// 		} else {
	// 			$w = new Control_Date_Range();
	// 		}
	// 		$w->names($name, $label, "");
	// 		$w->required($required);
	// 		return $w;
	// 	}


	/**
	 * Create a new View_ widget which is a date range view.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @return Control_DateRange
	 */
	// 	public static function view_date_range($name, $label = false, $start_column = false, $end_column = false) {
	// 		$w = new View_Date_Range();
	// 		$w->names($name, $label, "");
	// 		if ($start_column) {
	// 			$w->set_option("StartDateColumn", $start_column);
	// 		}
	// 		if ($end_column) {
	// 			$w->set_option("EndDateColumn", $start_column);
	// 		}
	// 		return $w;
	// 	}


	/**
	 * Create a new Control_ widget which is a checkbox.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $default The default value of this checkbox.
	 * @return Control_Checkbox
	 */
	public static function control_checkbox($name, $label = "", $default = false) {
		zesk::deprecated();
		$w = new Control_Checkbox();
		$w->names($name, $label, "");
		return $w;
	}

	/**
	 * Create a new Control_ widget which is a checkbox.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param array $options An associative array which is the list of the checkboxes with their associated values.
	 * @return Control_Checklist
	 */
	public static function control_checklist($name, $label, $options, $required = false) {
		zesk::deprecated();
		$w = new Control_Checklist(array(
			"options" => $options
		));
		$w->names($name, $label, "");
		$w->required($required);
		return $w;
	}

	/**
	 * Create a new Control_ widget which is a checkbox.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param array $options An associative array which is the list of the checkboxes with their associated values.
	 * @return Control_Checklist
	 */
	public static function control_checklist_text($name, $label, $options) {
		$w = new Control_Checklist_Text(array(
			"options" => $options
		));
		$w->names($name, $label, "");
		return $w;
	}

	/**
	 * Create a new drop-down control widget.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param array $options An associative array of options in the drop-down control.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @return Control_Select
	 */
	public static function control_select($name, $label, $options, $required = false) {
		zesk::deprecated();
		$w = new Control_Select();
		$w->names($name, $label, "");
		$w->required($required);
		if ($label === "") {
			$w->set_option("nolabel", true);
		}
		$w->set_option("options", $options);
		return $w;
	}

	/**
	 * Create a new drop-down control widget.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param array $options An associative array of options in the drop-down control.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @return Control_Select
	 */
	public static function control_radio($name, $label = false, $options = false, $required = false) {
		zesk::deprecated();
		$w = new Control_Radio();
		$w->names($name, $label, "");
		$w->required($required);
		if ($label === "") {
			$w->set_option("nolabel", true);
		}
		$w->set_option("options", $options);
		return $w;
	}

	/**
	 * Create a new drop-down control widget which selects an object from the database.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param string $table The database table to select from.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @return Control_Select_Object
	 */
	public static function control_select_object($name, $label = false, $table, $required = false) {
		zesk::deprecated();
		$w = new Control_Select_Object();
		$w->names($name, $label, "");
		$w->required($required);
		$w->set_option("Default", null);
		if ($label === "") {
			$w->set_option("nolabel", true);
		}
		$w->set_option("table", $table);
		return $w;
	}

	/**
	 * Create a new drop-down control widget which selects an object from the database.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param string $table The database table to select from.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @return Control_Select_Object
	 */
	public static function control_select_class($name, $label = true, $class = null, $required = false) {
		zesk::deprecated();
		if ($class === null) {
			$class = $name;
		}
		$w = new Control_Select_Object();
		$w->names($name, $label, "");
		$w->required = $required;
		$w->default = null;
		$w->_class($class);
		$w->table = Object::class_table_name($class);
		return $w;
	}

	/**
	 * Create a new drop-down control widget which selects an object from the database.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param string $table The database table to select from.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @return Control_Select_Object
	 */
	public static function control_select_object_dynamic($name, $label = false, $table, $required = false) {
		zesk::deprecated();
		$w = new Control_Select_Object_Dynamic();
		$w->names($name, $label, "");
		$w->required($required);
		$w->set_option("Default", null);
		if ($label === "") {
			$w->set_option("nolabel", true);
		}
		$w->set_option("table", $table);
		return $w;
	}

	/**
	 * Create a new drop-down control widget which selects an object from the database.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param string $table The database table to select from.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @return Control_Select_ObjectHierarchy
	 */
	public static function control_select_object_hierarchy($name, $label = false, $table, $required = false) {
		zesk::deprecated();
		$w = new Control_Select_Object_Hierarchy();
		$w->names($name, $label, "");
		$w->required($required);
		if ($label === "") {
			$w->set_option("nolabel", true);
		}
		$w->set_option("table", $table);
		return $w;
	}

	/**
	 * Create a new drop-down control widget which selects a file from the server-side file system.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @param string $sPath The path from the root of the files to populate the select list with.
	 * @param string $sFilter A wildcard filter for selecting only files of a certain type.
	 * @return Control_SelectFile
	 */
	public static function control_select_file($name, $label = false, $required = false, $sPath = false, $sFilter = false) {
		zesk::deprecated();
		$w = new Control_Select_File();
		$w->names($name, $label, "");
		$w->required($required);
		if (!empty($sPath)) {
			$w->set_option("path", $sPath);
		}
		if (!empty($sFilter)) {
			$w->set_option("filter", $sFilter);
		}
		return $w;
	}

	/**
	 * Create a new Control_ widget to reorder a list.
	 * Typically used in lists of objects.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param string $table The table to use for ordering.
	 * @return Control_Order
	 */
	public static function control_order($name, $label = false, $table) {
		zesk::deprecated();
		$w = new Control_Order();
		$w->names($name, $label, "");
		$w->set_option("table", $table);
		$w->set_option("width", "1%");
		$w->set_option("list_order_by_default", true);
		return $w;
	}

	/**
	 * Create a new Control_ widget to add linked objects to this object.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param string $table The link table to link with.
	 * @param Object $widget The widget used to link to the other objects.
	 * @return Control_Link_Object
	 */
	public static function control_link_object($name, $label = false, $table, $widget) {
		zesk::deprecated();
		$w = new Control_Link_Object();
		$w->names($name, $label, "");
		$w->set_option("table", $table);
		$w->set_option("widget", $widget);
		return $w;
	}

	/**
	 * Create a new View_ widget to add linked objects to this object.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param string $table The link table to link with.
	 * @param Object $widget The widget used to link to the other objects.
	 * @return Control_LinkObject
	 */
	public static function view_link_object($name, $label = false, $table, $widget) {
		zesk::deprecated();
		$w = new Control_Link_Object();
		$w->names($name, $label, "");
		$w->set_option("table", $table);
		$w->set_option("widget", $widget);
		$w->set_option("ShowMore", false);
		return $w;
	}

	/**
	 * Create a new Control_ widget to upload an image file.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @param string $srcFormat The format string used to generate the image destination path.
	 * @return Control_Image
	 */
	public static function control_image($name, $label = false, $required = false, $srcFormat) {
		zesk::deprecated();
		$w = new Control_Image();
		$w->names($name, $label, "");
		$w->required($required);
		$w->set_option("src", $srcFormat);
		$w->set_option("dest_path", $srcFormat);
		return $w;
	}

	/**
	 * Create a new Control_ widget for uploading files.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param boolean $required Whether this widget requires a valid value.
	 * @return Control_File
	 */
	public static function control_file($name, $label = false, $required = false) {
		zesk::deprecated();
		$w = new Control_File();
		$w->names($name, $label, "");
		$w->required($required);
		return $w;
	}

	/**
	 * Create a new Control_ widget for selecting an object
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param Object $object The type of object to manage in the list.
	 * @param string $new_action The URI of the "new" action for creating new objects.
	 * @return Control_Select_Object
	 */
	public static function control_object($name, $label = false, $class = null) {
		zesk::deprecated();
		$w = new Control_Select_Object();
		$w->names($name, $label, "");
		if ($class === null) {
			$class = $name;
		}
		$w->_class($class);
		return $w;
	}

	/**
	 * Create a new View_ widget to display a checkbox value.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @return ViewCheckbox
	 */
	public static function view_checkbox($name, $label, $true_string = null, $false_string = null) {
		zesk::deprecated();
		$w = new View_Checkbox();
		if ($true_string !== null)
			$w->set_option("true_string", $true_string);
		if ($false_string !== null)
			$w->set_option("false_string", $false_string);
		$w->set_option("truevalue", _G("ControlCheckbox::default_truevalue", 1));
		$w->set_option("falsevalue", _G("ControlCheckbox::default_falsevalue", 0));
		$w->names($name, $label, "");
		return $w;
	}

	/**
	 * Create a new View_ widget to display a value if a field is NULL or not
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @return ViewCheckbox
	 */
	public static function view_null_check($name, $label, $null_string = "NULL", $non_null_string = "Non-NULL") {
		zesk::deprecated();
		$w = new View_Checkbox();
		$w->set_option("null_check", true);
		$w->set_option("true_value", null);
		$w->set_option("true_string", $null_string);
		$w->set_option("false_string", $non_null_string);
		$w->names($name, $label, "");
		return $w;
	}

	/**
	 * Create a new Control_ widget which is a checkbox.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param array $options An associative array which is the list of the checkboxes with their associated values.
	 * @return Control_Checklist
	 */
	public static function view_checklist($name, $label = false, $options) {
		zesk::deprecated();
		$w = new View_Checklist(array(
			"options" => $options
		));
		$w->names($name, $label, "");
		return $w;
	}

	/**
	 * Create a new View_ widget which displays a value from a select list.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param array $options An associative array of options to display.
	 * @param mixed $default The value to display if the value is not found in the list.
	 * @return ViewSelect
	 */
	public static function view_select($name, $label = false, $options, $default = false) {
		zesk::deprecated();
		$w = new View_Select();
		$w->names($name, $label, "");
		if ($label === "") {
			$w->set_option("nolabel", true);
		}
		$w->set_option("options", $options);
		$w->set_option("default", $default);
		return $w;
	}

	/**
	 * Create a new View_ widget which displays a value from the database.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param string $table The database table to display.
	 * @param string $format The format to use when displaying the text of this widget.
	 * @return ViewObject
	 */
	public static function view_object($name, $label = false, $table, $format) {
		zesk::deprecated();
		$w = new View_Object();
		$w->names($name, $label, "");
		$w->set_option("format", $format);
		if (!empty($table)) {
			$w->set_option("table", $table);
		}
		return $w;
	}
	public static function view_template($template, $label = false, $options = null) {
		zesk::deprecated();
		$w = new View_Template(array(
			"Template" => $template
		));
		$w->names("", $label);
		$w->set_option($options);
		return $w;
	}
	public static function view_ip($name, $label = false) {
		zesk::deprecated();
		$w = new View_IP();
		$w->names($name, $label);
		return $w;
	}

	/**
	 * Create a new View_ widget which displays a value from the database.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param string $table The database table to display.
	 * @param string $format The format to use when displaying the text of this widget.
	 * @return Control_Hidden
	 */
	public static function control_hidden($name, $default = null) {
		zesk::deprecated();
		$w = new Control_Hidden();
		$w->names($name, "", "");
		if ($default !== null) {
			$w->default_value($default);
		}
		return $w;
	}

	/**
	 * Create a new View_ widget which displays a link.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param string $format The format to use when displaying the text of this widget.
	 * @param string $href The format to use when displaying the link.
	 * @return View_Link
	 */
	public static function view_link($name, $label = false, $format = false, $href = "") {
		zesk::deprecated();
		$w = new View_Link();
		$w->names($name, $label, "");
		$w->set_option("format", empty($format) ? '{' . $name . '}' : $format);
		if (empty($href)) {
			$w->action = "edit";
		} else {
			$w->href = $href;
		}
		return $w;
	}

	/**
	 * Create a new View_ widget which displays some formatted text.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param integer $sShowSize The number of characters to display, or negative numbers to show all text.
	 * @return View_Text
	 */
	public static function view_text($name, $label = false, $sShowSize = -1) {
		zesk::deprecated();
		$w = new View_Text();
		$w->names($name, $label, "");
		$w->set_option("show_size", $sShowSize);
		return $w;
	}

	/**
	 * Create a new View_ widget which displays some formatted text.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param integer $sShowSize The number of characters to display, or negative numbers to show all text.
	 * @return ViewText
	 */
	public static function view_integer($name, $label = false) {
		zesk::deprecated();
		$w = new View_Integer();
		$w->names($name, $label, "");
		return $w;
	}

	/**
	 * Create a new View_ widget which displays some formatted text.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param integer $sShowSize The number of characters to display, or negative numbers to show all text.
	 * @return ViewText
	 */
	public static function view_bytes($name, $label = false) {
		zesk::deprecated();
		$w = new View_Bytes();
		$w->names($name, $label, "");
		return $w;
	}

	/**
	 * Create a new View_ widget which displays some formatted text.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param integer $sShowSize The number of characters to display, or negative numbers to show all text.
	 * @return ViewText
	 */
	public static function view_email($name, $label = false, $sShowSize = -1) {
		zesk::deprecated();
		$w = new View_Email();
		$w->names($name, $label, "");
		$w->set_option("show_size", $sShowSize);
		return $w;
	}

	/**
	 * Create a new View_ widget which displays a formatted real number
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param integer $sformat The sprintf format for outputting the real number
	 */
	public static function view_real($name, $label = false, $decimal_places = 2) {
		zesk::deprecated();
		$w = new View_Real();
		$w->names($name, $label, "");
		if (is_numeric($decimal_places)) {
			$w->set_option("decimal_places", $decimal_places);
		}
		return $w;
	}

	/**
	 * Create a new View_ widget which displays some formatted text.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param string $currency The currency to display
	 * @return ViewText
	 */
	public static function view_currency($name, $label = false, $currency = false) {
		zesk::deprecated();
		$w = new View_Currency();
		$w->names($name, $label, "");
		if ($currency) {
			$w->set_option("currency", $currency);
		}
		return $w;
	}

	/**
	 * Create a new View_ widget which displays a date.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param string $format The format to use when displaying the text of this widget.
	 * @return ViewDate
	 */
	// 	public static function view_date($name, $label = false, $format = null) {
	// 		$w = new View_Date();
	// 		$w->names($name, $label, "");
	// 		if (!empty($format)) {
	// 			$w->format = $format;
	// 		}
	// 		return $w;
	// 	}


	/**
	 * Create a new View_ widget which breaks a form into distinct sections
	 * @param string $label The label to identify this section
	 * @return ViewDate
	 */
	public static function view_section($label) {
		zesk::deprecated();
		$w = new View_Section();
		$w->names($label, $label, "");
		return $w;
	}
	public static function view_section_end() {
		return new View_Section_End();
	}

	/**
	 * Create a new View_ widget which displays a date.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param string $format The format to use when displaying the text of this widget.
	 * @return ViewDate
	 */
	public static function view_time_span($name, $label) {
		zesk::deprecated();
		$w = new View_Time_Span();
		$w->names($name, $label, "");
		return $w;
	}

	/**
	 * Create a new View_ widget which displays an image.
	 * @param string $name
	 * @param string $label The label to identify this widget in lists and edit controls. Specify the empty string ""
	 *        for no label.
	 * @param string $srcFormat A format string which determines how the image source is determined.
	 * @param string $altFormat A format string which is used to generate the alt tag.
	 * @return unknown
	 */
	public static function view_image($name, $label, $srcFormat, $altFormat = "") {
		zesk::deprecated();
		$w = new View_Image();
		$w->names($name, $label, "");
		if ($altFormat != "") {
			$w->set_option("alt", $altFormat);

			// $w->set_option("title", $altFormat);
		}
		$w->set_option("src", $srcFormat);
		return $w;
	}
	public static function control_icon($name, $label, $icons) {
		zesk::deprecated();
		$w = new Control_Icon();
		$w->names($name, $label);
		$w->set_option("icons", $icons);
		return $w;
	}
	public static function control_slider($name, $label, $options = false) {
		zesk::deprecated();
		$w = new Control_Slider($options);
		$w->names($name, $label);
		return $w;
	}
	public static function control_schedule($name, $label, $options = false) {
		zesk::deprecated();
		$w = new Control_Schedule($options);
		$w->names($name, $label);
		return $w;
	}

	// 	public static function publish_start_end(&$spec) {
	// 		/* @var $factory WidgetFactory */
	// 		$f = self::control_checkbox("IsActive", "Published?", 'true');
	// 		$spec[$f->column()] = $f;


	// 		$f = self::control_date("PublishStart", "Begin publishing on", false);
	// 		$f->set_option("empty_string", "Immediate");
	// 		$spec[$f->column()] = $f;


	// 		$f = self::control_date("PublishEnd", "End publishing on", false);
	// 		$f->set_option("empty_string", "Forever");
	// 		$spec[$f->column()] = $f;
	// 	}
	public static function control_list_checkbox($idname = "ID", $header_attributes = null, $row_attributes = null) {
		zesk::deprecated();
		html::cdn_javascript('/share/zesk/js/zesk.js', array(
			'weight' => 'first'
		));

		$h = self::control_checkbox("IDCheckAll", "");
		$h->set_option("onclick", "ListControl_CheckAll(this,'" . $idname . "')");
		$h->set_option("class", "checkbox");
		$h->set_option($header_attributes);

		$f = self::control_checkbox($idname, __("Select"));
		$f->set_option("class", "checkbox");
		$f->set_option("headwidget", $h);
		$f->set_option("list_order_by", false);
		$f->set_option("width", "1%");
		$f->name($idname . "[]");
		$f->set_option("truevalue", '{' . $idname . '}');
		$f->set_option("checked", false);
		$f->set_option("onclick", "ListControl_CheckItem(this,'" . $idname . "')");
		$f->set_option("align", "center");

		return $f;
	}

	public static function fieldset($idname, $label) {
		zesk::deprecated();
		$w = Widget::factory("Control_FieldSet");
		$w->id = $idname;
		$w->column = $idname;
		$w->label($label);
		return $w;
	}
}
