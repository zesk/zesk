<?php

/* $var $widget Control_Checklist */
$widget = $this->widget;

if (count($this->children) === 0) {
	$widget->nowrap();
	return;
}

$name = $widget->name();
/* @var $object Object */
$object = $this->object;

echo html::input_hidden($name . '_sv', 1);

$exclusives = array();
$inputs = array();
foreach ($this->children as $child) {
	/* @var $child Widget */
	$inputs[] = $child->render();
	if ($child->option_bool(Control_Checklist::option_checklist_exclusive)) {
		$exclusives[] = "#" . $child->id();
	}
}

$columns = intval($this->columns);
if ($columns > 1) {
	echo html::div_open('.row');
	$columns = clamp(2, $columns, 12);
	$columns = avalue(arr::flip_copy(to_list("2;3;4;6;12")), $columns, 12);
	$n_per = ceil(count($inputs) / $columns);
	for($i = 0; $i < $columns; $i++) {
		echo html::tag('div', '.col-sm-' . intval(12 / $columns), implode("\n", array_slice($inputs, $i * $n_per, $n_per)));
	}
	echo html::div_close();
} else {
	echo implode("\n", $inputs);
}

if (count($exclusives) > 0) {
	$map = array();
	$map['exclusives'] = implode(",", $exclusives);
	ob_start();
	?><script>
	(function ($) {
		var update = function () {
			var
			$this = $(this),
			$this_label = $this.parents("label"),
			$group = $this.parents(".control-checklist"),
			name = $this.attr("name"),
			id = $this.attr("id"),
			checked = $this.prop("checked");
			$other_labels = $("input[name='" + name + "']:not([id='" + id +"'])", $group).parents(".checkbox").find("label");

			$other_labels.toggle(!checked);
			$other_labels.find('input').prop("disabled", checked);
		};
		$("{exclusives}").on("click", update).each(update);
	}(window.jQuery));
	</script><?php
	$content = html::extract_tag_contents("script", map(ob_get_clean(), $map));
	$this->response->jquery($content);
}