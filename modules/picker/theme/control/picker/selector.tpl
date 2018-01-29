<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
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
$column = $this->column;

$id = "control-picker-selector-q-$column";

$picker_options = $this->geta("picker_options", array());
if (is_array($this->data_search) && count($this->data_search) > 0) {
	$picker_options['data_search'] = $this->data_search;
}

$json_options = count($picker_options) > 0 ? JSON::encode($picker_options) : "";
$this->response->jquery("\$(\"#$id\").picker($json_options);");

$input_attributes = array(
	'class' => "input-lg required form-control",
	"id" => $id,
	'name' => "q",
	'type' => "text",
	'placeholder' => __($this->label_search),
	'data-source' => $this->target,
	'data-widget-target' => $this->name
);

echo HTML::div_open('.control-picker-selector');
echo HTML::div('.form-group control-text', HTML::tag('input', $input_attributes, null));
if (!$this->inline_picker) {
	?>
<div class="form-group control-button full-width">
	<button class="btn btn-primary submit" name="ok"><?php echo __($this->label_save); ?></button>
</div>
<?php
}
echo HTML::etag('div', array(
	'class' => 'control-picker-none-selected',
	'style' => 'display: none'
), $this->item_selector_none_selected);
echo HTML::etag('div', '.control-picker-empty', $this->item_selector_empty);

?>
<div
	class="control-picker-results class-<?php echo $this->object_class_css_class; ?>">
<?php
foreach ($this->objects as $object) {
	$item_content = $this->theme($this->theme_item, array(
		"object" => $object,
		"selected" => true,
		"column" => $this->column
	));
	echo $item_content;
}
?>
</div>
<?php
echo HTML::div_close();
