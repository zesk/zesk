<?php
$request = $this->request;

$contact = $this->contact;

$account = $this->account;
$person = $this->person;

$this->response->jquery();
$this->response->cdn_javascript('/share/zesk/jquery/jquery.overlabel.js');
$this->response->cdn_javascript('/share/zesk/jquery/jquery.glow.js');

/* @var $request Request */
/* @var $account Account */
/* @var $contact Contact */

if (!$account) {
	$account = $contact->Account;
}
if (!$account) {
	throw new Exception_Semantics("Need an account to edit contacts");
}
$id = $contact->is_new() ? 0 : $contact->id();

if (!isset($person) || !$person instanceof Contact_Person) {
	$person = $contact->Person;
}
function contact_edit_post(Template $object, Contact $contact) {
	$request = $object->request;
	$account = $object->account;
	$person = $contact->Person;
	$person_fields = array(
		"Prefix",
		"FirstName",
		"MiddleName",
		"LastName",
		"Suffix",
		"Title",
		"Company",
		"Nickname"
	);
	foreach ($person_fields as $person_field) {
		$request_field = "Person_$person_field";
		if ($request->has($request_field)) {
			$person->set_member($person_field, $request->get($request_field));
		}
	}
	$errors = array();
	$value_names = array(
		"Email" => array(
			"Emails",
			Contact_Label::LabelType_Email
		),
		"Phone" => array(
			"Phones",
			Contact_Label::LabelType_Phone
		),
		"Address" => array(
			"Addresses",
			Contact_Label::LabelType_Address
		),
		"URL" => array(
			"URLs",
			Contact_Label::LabelType_URL
		),
		"Date" => array(
			"Dates",
			Contact_Label::LabelType_Date
		),
		"Other" => array(
			"Others",
			Contact_Label::LabelType_Other
		)
	);
	$delete_list = array();
	foreach ($value_names as $value_name => $settings) {
		list($member_name, $label_type) = $settings;
		$labels = $request->get("Contact_" . $value_name . "_Label");
		$labels_custom = $request->get("Contact_" . $value_name . "_Custom");
		$values = $request->get("Contact_" . $value_name);
		if (!is_array($labels) || !is_array($values)) {
			$errors[] = "Incorrect POST format for $value_name";
			continue;
		}
		$linked_ids = array_flip($contact->links($member_name));
		$registered_ids = array();
		foreach ($values as $index => $value) {
			$value = trim($value);
			if (empty($value)) {
				continue;
			}
			$label = avalue($labels, $index);
			$label_custom = avalue($labels_custom, $index);
			$subobject = $contact->find_linked_data($member_name, $value);
			if (!$subobject) {
				$subobject = Object::factory("Contact_" . $value_name);
			} else {
				$subobject_id = $subobject->id();
				unset($linked_ids[$subobject_id]);
				$registered_ids[] = $subobject_id;
			}
			$subobject->Value = $value;
			if (intval($label_custom) !== 0) {
				$subobject->Label = Contact_Label::register_local($label_type, $label, $account);
			} else {
				// TODO permission
				$subobject->Label = $label;
			}
			$contact->$member_name = $subobject;
		}
		$linked_ids = array_keys($linked_ids);
		foreach ($linked_ids as $id) {
			$delete_list[] = Object::factory("Contact_" . $value_name, $id);
		}
	}
	$contact->Notes = $request->get("Contact_Notes");
	if (count($errors) > 0) {
		return $errors;
	}
	if ($contact->store()) {
		foreach ($delete_list as $object) {
			$object->delete();
		}
		return true;
	}
	return false;
}

if ($request->is_post()) {
	$result = contact_edit_post($this, $contact);
	if ($result === true) {
		$response = array(
			"success" => $result,
			"message" => __("Saved successfully.")
		);
	} else {
		$response = array(
			"success" => false,
			"message" => __("There were some problems saving your contact."),
			"errors" => $result
		);
	}
	$response['id'] = $contact->id();
	$this->json = true;
	echo json::encode($response);
	return;
}

/* @var $request Request */
function contact_edit_input($label, $class, $name, $id, $value, $default_visibility = true, $multi = false, $widget_textarea = false) {
	if (empty($id)) {
		$id = $name;
	}
	$widget_attrs = array(
		'name' => $name . ($multi ? '[]' : ""),
		'class' => $name,
		'id' => $id
	);
	$widget_attrs['class'] = $class;
	if ($widget_textarea) {
		$widget = html::tag("textarea", $widget_attrs, htmlspecialchars("$value"));
	} else {
		$widget_attrs['type'] = 'text';
		$widget_attrs['value'] = $value;
		$widget = html::tag("input", $widget_attrs);
	}
	
	$attrs = array(
		"class" => 'overlabel-pair edit-value',
		"id" => "contact-field-$name"
	);
	if ($default_visibility === false && empty($value)) {
		$attrs['style'] = 'display: none';
	}
	return html::div($attrs, html::tag('label', array(
		'for' => $id,
		"class" => "overlabel"
	), $label) . $widget);
}
function contact_edit_pair($label, $class, $name, $data, $labels, $section, $section_id, $widget_textarea = false) {
	$value = avalue($data, 'Value');
	$label_value = avalue($data, 'Label');
	$field_name = $name . "_Label[]";
	$is_custom = intval(count($labels) === 0);
	$id_label = 'label-' . $section_id;
	if ($is_custom) {
		$label_html = html::div('.overlabel-pair', html::tag('label', array(
			'class' => 'overlabel',
			'for' => $id_label
		), __('Label')) . html::input('text', $field_name, '', array(
			'id' => $id_label,
			'class' => 'label-custom'
		)));
	} else {
		$labels['...'] = __('Custom ...');
		$w = widgets::control_select($field_name, null, $labels, true);
		$w->set_option("onchange", "contact_label_change.call(this)");
		$data[$field_name] = $label_value;
		$label_html = $w->output($data);
	}
	return html::div('.contact-pair', contact_edit_input($label, $class, $name, $id_label, $value, true, true, $widget_textarea) . html::div('.contact-label', $label_html) . "<a href=\"javascript:contact_remove_item('$section','$section_id')\" class=\"remove\">" . __('remove') . "</a>" . html::input_hidden($name . "_Custom[]", "$is_custom", array(
		"class" => "custom"
	)));
}

$require_labels = $this->require_labels;
$required_labels = array();
if (is_array($require_labels)) {
	$required_labels = array();
	foreach ($require_labels as $label) {
		$required_labels[$label->Type][$label->id()] = $label;
	}
}

$sections = array(
	'email' => array(
		'label_type' => Contact_Label::LabelType_Email,
		'head_label' => "Email",
		'object_name' => "Contact_Email"
	),
	'phone' => array(
		'label_type' => Contact_Label::LabelType_Phone,
		'head_label' => "Phone",
		'object_name' => "Contact_Phone"
	),
	'address' => array(
		'label_type' => Contact_Label::LabelType_Address,
		'head_label' => "Address",
		'object_name' => "Contact_Address",
		'field_type' => 'textarea'
	),
	'url' => array(
		'label_type' => Contact_Label::LabelType_URL,
		'head_label' => "Web sites",
		'object_name' => "Contact_URL",
		'widget_class' => 'long'
	),
	'dates' => array(
		'label_type' => Contact_Label::LabelType_Date,
		'head_label' => "Dates",
		'object_name' => "Contact_Date",
		'default_hide' => true
	),
	'other' => array(
		'label_type' => Contact_Label::LabelType_Other,
		'head_label' => "Other",
		'object_name' => "Contact_Other",
		'default_hide' => true
	)
);

html::jquery("contact_edit_load()");

$fields = array(
	'Person_Prefix' => array(
		'person',
		'Prefix'
	),
	'Person_MiddleName' => array(
		'person',
		'Middle Name'
	),
	'Person_Suffix' => array(
		'person',
		'Suffix'
	),
	'Person_Title' => array(
		'person',
		'Title'
	),
	'Person_Company' => array(
		'person',
		'Company'
	),
	array(
		'email',
		'Email'
	),
	array(
		'phone',
		'Phone'
	),
	array(
		'address',
		'Address'
	),
	array(
		'url',
		'Website'
	),
	array(
		'dates',
		'Date'
	),
	array(
		'other',
		'Custom'
	)
)?>
<form id="contact-form-<?php echo $id?>" action="<?php echo url::current()?>"
	method="post"
	onsubmit="contact_save('contact-form-<?php echo $id?>', <?php echo $this->get('onsave', 'contact_view')?>); return false">
	<input name="hash" value="<?php echo $this->get('hash')?>" id="hash"
		type="hidden" />
	<div id="contact-edit-<?php echo $id?>" class="contact-edit">
		<input type="submit" name="OK" value="Save" class="action-button" />
		<div class="add-field">
			<a onclick="$('.add-fields').toggle();" class="add-field-button">Add
				field</a>
			<div class="add-fields" style="display: none">
				<ul><?php
				foreach ($fields as $codename => $field) {
					list($section, $name) = $field;
					if (is_string($codename)) {
						$onclick = "$('#contact-field-$codename').show();$('#$codename-add').hide(); \$(this).parent().parent().parent().hide()";
						$link = html::tag("a", array(
							"onclick" => $onclick,
							"id" => $codename . "-add"
						), $name);
					} else {
						$onclick = "contact_add_item('$section'); \$(this).parent().parent().parent().hide()";
						$link = html::tag("a", array(
							"onclick" => $onclick
						), $name);
					}
					echo html::tag('li', $link);
				}
				?></ul>
			</div>
		</div>
		<div class="contact-person">
			<table class="layout">
				<tr>
					<td><?php echo contact_edit_input(__("Prefix"), "short", "Person_Prefix", null, $person->member("Prefix"), false)?></td>
					<td><?php echo contact_edit_input(__("First Name"), null, "Person_FirstName", null, $person->member("FirstName"), true)?></td>
					<td><?php echo contact_edit_input(__("Middle Name"), null, "Person_MiddleName", null, $person->member("MiddleName"), false)?></td>
					<td><?php echo contact_edit_input(__("Last Name"), null, "Person_LastName", null, $person->member("LastName"), true)?></td>
					<td><?php echo contact_edit_input(__("Suffix"), "short", "Person_Suffix", null, $person->member("Suffix"), false)?></td>
				</tr>
			</table>
			<table>
				<tr>
					<td><?php echo contact_edit_input(__("Title"), null, "Person_Title", null, $person->member("Title"), false)?></td>
					<td><?php echo contact_edit_input(__("Company"), null, "Person_Company", null, $person->member("Company"), true)?></td>
					<td><?php echo contact_edit_input(__("Nickname"), null, "Person_Nickname", null, $person->member("Nickname"), false)?></td>
				</tr>
			</table>
		</div>
<?php
$append_templates = array();
foreach ($sections as $section => $variables) {
	$label_type = $head_label = $object_name = $field_type = $widget_class = $default_hide = null;
	;
	extract($variables, EXTR_IF_EXISTS);
	$values = array();
	if ($id) {
		$values = Objet::class_query($object_name)->what(array(
			"Label" => "Label",
			"Value" => "Value"
		))->where("contact", $id)->to_array();
	}
	$display = "";
	$required = avalue($required_labels, $label_type, array());
	if (!$id || count($values) === 0) {
		if (count($required) > 0) {
			$values = array();
			foreach ($required as $required) {
				$values[] = array(
					'Label' => $required->id(),
					'Value' => '',
					'required' => true
				);
			}
		} else {
			$values = array(
				array(
					'Label' => null,
					'Value' => ''
				)
			);
			if ($default_hide) {
				$display = ' style="display: none"';
			}
		}
	}
	$labels = Contact_Label::label_options($label_type, $account->id());
	?>
	<div class="contact-section" id="contact-section-<?php echo $section?>"
			<?php echo $display?>>
			<div class="section-head">
				<h2><?php echo __($head_label)?></h2>
				<a href="javascript:contact_add_item('<?php echo $section?>')">Add</a>
			</div>
		<?php
	foreach ($values as $i => $data) {
		$section_id = "$object_name-$id-$i";
		$required = avalue($data, 'required', false);
		?><div
				class="section-control section-control-<?php echo $section?><?php echo $required ? " section-label-required" : ""?>"
				id="<?php echo $section_id?>">
			<?php echo contact_edit_pair(__($head_label), $widget_class, $object_name, $data, $labels, $section, $section_id, $field_type === "textarea")?>
		</div>
		<?php
	}
	?>
		<?php
	ob_start();
	?>
		<div id="section-template-<?php echo $section?>" style="display: none">
			<?php echo contact_edit_pair(__($head_label), $widget_class, $object_name, array(), $labels, $section, '{id}', $field_type === "textarea")?>
		</div>
		<?php
	$append_templates[] = ob_get_clean();
	?>
	</div>
<?php
}
?>
	<div class="contact-section" id="contact-section-notes">
			<div class="section-head">
				<h2><?php echo __('Notes')?></h2>
			</div>
			<div class="section-control section-control-notes" id="Contact_Notes">
				<textarea name="Contact_Notes" rows="6"><?php echo htmlspecialchars($contact->Notes)?></textarea>
			</div>
		</div>
	</div>
</form>
<div id="label-template" style="display: none">
	<div class="overlabel-pair">
		<label class="overlabel" for="{id}"><?php echo __('Label')?></label><input
			type="text" name="{name}" id="{id}" class="label-custom" />
	</div>
</div>
<?php echo implode("\n", $append_templates)?>
