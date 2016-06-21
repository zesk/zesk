<?php

/**
 * Control_Text_Dropdown render template
 * 
 */
/* @var $this Template */
if (false) {
	$response = $this->response;
	/* @var $response Response_HTML */
	$object = $this->object;
	/* @var $object Model */
	$widget = $this->widget;
	/* @var $widget Control_Text_Dropdown */
	$name = $this->name;
	/* @var $name string */
	$value = $this->value;
	$variables = $this->variables;
}
$ia = $this->geta("attributes");

$ia['id'] = $id = $this->id;
$ia["name"] = $name;

$class = $this->class;
if ($this->required) {
	$class = css::add_class($class, "required");
}
$ia['class'] = css::add_class($class, 'form-control');

$ia['placeholder'] = $this->placeholder;

$ia = $object->apply_map($ia) + array(
	'value' => $value
);

$button_label = $this->button_label;

$side = $this->get("dropdown_alignment", "right");

$html = "";

$html .= html::div_open('.input-group-btn');

$html .= html::tag('button', array(
	'type' => 'button',
	'class' => 'btn btn-default dropdown-toggle',
	'data-toggle' => 'dropdown',
	'data-content' => '{label} ' . html::span('.caret', ''),
	'aria-expanded' => 'false'
), $button_label . ' ' . html::span('.caret', ''));

$html .= html::tag_open('ul', array(
	"class" => "dropdown-menu dropdown-menu-$side",
	"role" => "menu"
));

$dropdown_value = $this->dropdown_value;
if (!$dropdown_value) {
	$dropdown_value = $this->dropdown_default;
}
if ($this->select_behavior_enabled && empty($dropdown_value)) {
	foreach ($this->dropdown_menu as $code => $attributes) {
		if ($attributes === '-') {
			continue;
		}
		if ($dropdown_value === null) {
			$dropdown_value = $code;
		}
		if (!is_array($attributes)) {
			continue;
		}
		if (to_bool(avalue($attributes, 'selected'))) {
			$dropdown_value = $code;
			break;
		}
	}
}
foreach ($this->dropdown_menu as $code => $attributes) {
	if ($attributes === '-') {
		$html .= html::tag('li', '.divider', '');
		continue;
	}
	if (is_string($attributes)) {
		$attributes = array(
			'link_html' => $attributes
		);
	}
	$attributes += array(
		'data-value' => $code
	);
	if (array_key_exists('list_item_attributes', $attributes)) {
		$li_attributes = $attributes['list_item_attributes'];
		unset($attributes['list_item_attributes']);
	} else {
		$li_attributes = array();
	}
	if (array_key_exists('link_html', $attributes)) {
		$link_html = $attributes['link_html'];
		unset($attributes['link_html']);
	} else {
		$link_html = $code;
	}
	if (to_bool(avalue($attributes, 'selected')) || $code === $dropdown_value) {
		$li_attributes = html::add_class($li_attributes, "active");
	}
	$html .= html::tag('li', $li_attributes, html::tag('a', $attributes, $link_html));
}
$html .= html::tag_close('ul');
$html .= html::div_close(); // input-group-btn


echo html::div_open('.input-group');
if ($side === "left") {
	echo $html;
}
echo html::tag("input", $ia);
if ($side !== "left") {
	echo $html;
}
echo html::div_close(); // input-group


echo html::input('hidden', $this->dropdown_name, $dropdown_value, array(
	'id' => $this->dropdown_id
));

if ($this->select_behavior_enabled) {
	ob_start();
	?><script>
(function () {
	var
	support_plural = {support_plural},
	$input_group = $('#{id}').parents('.input-group'),
	$input = $('input.form-control', $input_group),
	update = function () {
		var
		$selected = $('li.active a', $input_group),
		$button = $('button.dropdown-toggle', $input_group),
		content = $button.data("content") || '{label}',
		label = $selected.data('content') || '{noun}',
		noun = $selected.data('noun') || $selected.html(),
		value = $input.val(),
		dropdown_value = $selected.data('value');
		if (support_plural) {
			noun = Locale.plural(noun, parseInt(value, 10) || 1);
		}
		label = label.map({ noun: noun });
		$button.html(content.map({ label: label }));
		$('#{dropdown_id}').val(dropdown_value);
	};
	update();
	$('li a', $input_group).on("click", function () {
		$('li.active', $input_group).removeClass('active');
		$(this).parents('li').addClass('active');
		update();
	});
	$input.on('change', function () {
		update();
	});
}());
</script>
<?php
	$content = html::extract_tag_contents('script', ob_get_clean());
	$response->jquery(map($content, array(
		'id' => $id,
		'dropdown_id' => $this->dropdown_id,
		'support_plural' => json::encode($this->plural_behavior_enabled)
	)));
}

/*
    <div class="input-group">
      <input type="text" class="form-control" aria-label="...">
      <div class="input-group-btn">
        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">Action <span class="caret"></span></button>
        <ul class="dropdown-menu dropdown-menu-right" role="menu">
          <li><a href="#">Action</a></li>
          <li><a href="#">Another action</a></li>
          <li><a href="#">Something else here</a></li>
          <li class="divider"></li>
          <li><a href="#">Separated link</a></li>
        </ul>
      </div><!-- /btn-group -->
    </div><!-- /input-group -->

 */