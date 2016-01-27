<?php
/* @var $widget Widget */
$widget = $this->widget;

/* @var $object Model */
$object = $this->object;

/* @var $child Widget */
$child = $this->child;

/* @var $this Template */
$name = $widget->name();

$show_required = $widget->show_required;
$overlabel = $widget->overlabel;

$nolabel = $child->nolabel;
$fill_label = $child->fill_label;
$n_cols = $show_required ? 3 : 2;

if ($overlabel) {
	// $overlabel_id = "overlabel_". html::id_counter();
	$child->set_option("overlabel", true);
	$this->response->jquery();
	$this->response->cdn_javascript("/share/zesk/jquery/jquery.overlabel.js");
	$this->response->jquery("$('label.overlabel').overlabel();");
	// $widget->set_option("id", $overlabel_id);
}

$context_class = $child->context_class();
$class_parent = lists::append("input-control", $widget->option('class_parent', $context_class), ' ');
$class_row = lists::append("", $widget->option('class_row', "row-" . $context_class));

$data = $child->content;
$label = $child->label;
$id = $child->id;

echo html::tag_open("tr", "");
if ($fill_label) {
	if ($show_required) {
		echo html::tag("td", ".input-required", $widget->required() ? "*" : "&nbsp;");
	}
	echo html::tag("td", array(
		'colspan' => $n_cols, 
		'class' => $class_parent
	), $data);
} else if ($nolabel) {
	if (!$overlabel) {
		echo html::tag("td", $show_required ? array(
			"colspan" => 2
		) : false, "&nbsp;");
	} else if ($show_required) {
		echo html::tag("td", false, "&nbsp;");
	}
	echo html::tag("td", array(
		'class' => $class_parent
	), $data);
} else if ($overlabel) {
	if ($show_required) {
		echo html::tag("td", ".input-required", $widget->required() ? "*" : "&nbsp;");
	}
	echo html::tag("td", array(
		"class" => $class_parent
	), html::tag("div", array(
		"class" => "overlabel-pair"
	), html::tag("label", array(
		"class" => "overlabel", 
		"for" => $id
	), $label) . $data));
} else {
	if ($show_required) {
		echo html::tag("td", ".input-required", $widget->required() ? "*" : "&nbsp;");
	}
	echo html::tag("td", ".input-label", html::tag("label", array(
		"for" => $id
	), $label));
	echo html::tag("td", array(
		'class' => $class_parent, 
		'id' => $id
	), $data);
}
echo html::tag_close("tr");
