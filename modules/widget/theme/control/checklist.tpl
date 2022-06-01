<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
/* $var $widget Control_Checklist */
$widget = $this->widget;

if (count($this->children) === 0) {
	$widget->clearWrap();
	return;
}

$name = $widget->name();
/* @var $object ORM */
$object = $this->object;

echo HTML::input_hidden($name . '_sv', 1);

$exclusives = [];
$inputs = [];
foreach ($this->children as $child) {
	/* @var $child Widget */
	$inputs[] = $child->render();
	if ($child->optionBool(Control_Checklist::option_checklist_exclusive)) {
		$exclusives[] = '#' . $child->id();
	}
}

$columns = intval($this->columns);
if ($columns > 1) {
	echo HTML::div_open('.row');
	$columns = clamp(2, $columns, 12);
	$columns = avalue(ArrayTools::valuesFlipCopy(to_list('2;3;4;6;12')), $columns, 12);
	$n_per = ceil(count($inputs) / $columns);
	for ($i = 0; $i < $columns; $i++) {
		echo HTML::tag('div', '.col-sm-' . intval(12 / $columns), implode("\n", array_slice($inputs, $i * $n_per, $n_per)));
	}
	echo HTML::div_close();
} else {
	echo implode("\n", $inputs);
}

if (count($exclusives) > 0) {
	$map = [];
	$map['exclusives'] = implode(',', $exclusives);
	ob_start(); ?><script>
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
	$content = HTML::extract_tag_contents('script', map(ob_get_clean(), $map));
	$response->html()->jquery($content);
}
