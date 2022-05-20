<?php declare(strict_types=1);
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
/* @var $widget Widget */
/* @var $object Model */
/* @var $child Widget */
$name = $widget->name();

$show_required = $widget->show_required;
$overlabel = $widget->overlabel;

$nolabel = $child->nolabel;
$fill_label = $child->fill_label;
$n_cols = $show_required ? 3 : 2;

if ($overlabel) {
	// $overlabel_id = "overlabel_". HTML::id_counter();
	$child->setOption('overlabel', true);
	$response->jquery();
	$response->javascript('/share/zesk/jquery/jquery.overlabel.js');
	$response->jquery('$(\'label.overlabel\').overlabel();');
	// $widget->setOption("id", $overlabel_id);
}

$context_class = $child->context_class();
$class_parent = Lists::append('input-control', $widget->option('class_parent', $context_class), ' ');
$class_row = Lists::append('', $widget->option('class_row', 'row-' . $context_class));

$data = $child->content;
$label = $child->label;
$id = $child->id;

echo HTML::tag_open('tr', '');
if ($fill_label) {
	if ($show_required) {
		echo HTML::tag('td', '.input-required', $widget->required() ? '*' : '&nbsp;');
	}
	echo HTML::tag('td', [
		'colspan' => $n_cols,
		'class' => $class_parent,
	], $data);
} elseif ($nolabel) {
	if (!$overlabel) {
		echo HTML::tag('td', $show_required ? [
			'colspan' => 2,
		] : false, '&nbsp;');
	} elseif ($show_required) {
		echo HTML::tag('td', false, '&nbsp;');
	}
	echo HTML::tag('td', [
		'class' => $class_parent,
	], $data);
} elseif ($overlabel) {
	if ($show_required) {
		echo HTML::tag('td', '.input-required', $widget->required() ? '*' : '&nbsp;');
	}
	echo HTML::tag('td', [
		'class' => $class_parent,
	], HTML::tag('div', [
		'class' => 'overlabel-pair',
	], HTML::tag('label', [
		'class' => 'overlabel',
		'for' => $id,
	], $label) . $data));
} else {
	if ($show_required) {
		echo HTML::tag('td', '.input-required', $widget->required() ? '*' : '&nbsp;');
	}
	echo HTML::tag('td', '.input-label', HTML::tag('label', [
		'for' => $id,
	], $label));
	echo HTML::tag('td', [
		'class' => $class_parent,
		'id' => $id,
	], $data);
}
echo HTML::tag_close('tr');
