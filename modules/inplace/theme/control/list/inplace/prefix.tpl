<?php


/* @var $parent Widget */
$parent = $this->widget->parent();
/* @var $class_object Class_Object */
$class_object = $this->list_class_object;

$name = __($class_object->name);
$names = zesk\Locale::plural(__($class_object->name));
$new_url_button = "";
if (!$this->hide_new_button) {
	$new_url = $this->router->get_route("new", $this->list_class);
	if ($new_url && $this->current_user->can($this->list_class . "::new")) {
		$new_url_button = HTML::tag("button", array(
			"class" => "btn btn-warning",
			"data-modal-url" => $new_url,
			'data-target' => "#" . $parent->id()
		), __("Create {name}", array(
			"name" => $name
		)));
	}
}
echo HTML::tag('div', 'col-xs-12 col-md-5 pull-right', $new_url_button . HTML::tag('div', '.list-inplace-total', zesk\Locale::plural_word($this->list_object_name, $this->total)));

if (!$this->hide_title) {
	echo HTML::tag('h2', __("Manage list of {names}", array(
		"names" => $names
	)));
}
