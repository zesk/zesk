<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \User */
/* @var $object \zesk\Model */
$attr = $this->attributes;

$object = $this->object;
$name = $this->name;
$id = $this->id;
$col = $this->column;
$widget = $this->widget;

$attr['type'] = 'checkbox';
$attr['value'] = $this->checked_value;
$attr['class'] = $this->class;
$attr['name'] = $name;
$attr['id'] = $id ? $id : null;

$disabled_class = '';
if ($this->getBool('disabled')) {
	$disabled = $this->getBool('disabled');
	if ((is_bool($disabled) && $disabled) || strtolower($disabled) === 'disabled') {
		$attr['disabled'] = 'disabled';
		$disabled_class = ' disabled';
	}
}
$cont_name = $name . '_sv';
if ($this->getBool('refresh')) {
	$attr['onclick'] = "this.form.$cont_name.value=1;this.form.submit();";
}
if ($this->checked) {
	$attr['checked'] = 'checked';
}

$result = $this->input_prefix . HTML::tag('input', $object ? $object->applyMap($attr) : $attr, null) . $this->input_suffix;
if ($this->getBool('refresh')) {
	echo HTML::hidden($cont_name, '');
}

if ($this->label_checkbox) {
	$label_attr = ArrayTools::keysMap(ArrayTools::filter($attr, 'id'), [
		'id' => 'for',
	]);
	$label_attr['class'] = $this->get('checkbox_label_class', null);
	$result = HTML::tag('div', '.checkbox' . $disabled_class, HTML::tag('label', $label_attr, $result . $this->label_checkbox));
}
echo $result;
if (!ends($name, ']')) {
	echo HTML::hidden($name . '_ckbx', 1);
}
