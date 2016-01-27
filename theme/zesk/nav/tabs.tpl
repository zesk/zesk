<?php
$widget = $this->widget;
/* @var $widget Widget */
$upload = $widget ? $widget->upload() : false;

$form = array(
	'class' => css::add_class('form-horizontal', $this->class),
	'action' => $this->request->path(),
	'method' => 'post',
	'enctype' => $upload ? "multipart/form-data" : ""
);

echo html::tag_open('form', $form);

$prefix = array();
$navs = array();
$suffix = array();
foreach ($this->children as $widget) {
	if ($widget->option_bool('nav')) {
		$navs[] = $widget;
	} else if (count($navs) === 0) {
		echo $widget->render();
	} else {
		$suffix[] = $widget;
	}

}

/*
 * Standard tab functionality messes with # and causes the page to jump
 * This uses the hash in the URL, and prevents the page jump by temporarily
 * removing the id
 */
$this->response->jquery('$("[data-toggle=tab]").off("click.tabs").on("click.tabs", function (e) {
	var
	$this = $(this),
	$form = $this.parents("form"),
	href = $this.attr("href"),
	$target = $(href),
	id = href.substr(1);
	if ($form.length) {
		$form.attr("action", $form.attr("action").left("#") + href);
	}
	e.preventDefault();
	e.stopPropagation();
	$this.tab("show");
	$target.attr("id", "");
	document.location = href;
	$target.attr("id", id);
	return false;
});');

$this->response->jquery('$(".nav-tabs li:first a,li a[href=\"#" + document.URL.right("#") + "\"]").tab("show");');
$title = $this->response->title();
?>
<div class="nav-tabs">
	<ul class="nav nav-tabs">
	<?php
	$content = "";
	$selected_tab = $first_name = null;
	foreach ($this->children as $widget) {
		$name = $widget->column();
		if ($first_name === null) {
			$first_name = $name;
		}
		if ($name === $this->selected_tab) {
			$selected_tab = $name;
			break;
		}
	}
	if ($selected_tab === null) {
		$selected_tab = $first_name;
	}
	/* @var $widget Widget */
	foreach ($navs as $widget) {
		$widget_content = $widget->render();
		if (empty($widget_content)) {
			continue;
		}
		$errors = $widget->children_errors();
		$name = $widget->column();
		$item_class = $name === $selected_tab ? 'active' : '';
		$item_class = css::add_class($item_class, $widget->context_class());
		echo html::tag('li', array(
			'class' => css::add_class($item_class, count($errors) > 0 ? 'error' : '')
		), html::tag('a', array(
			'href' => '#' . $name,
			'data-toggle' => "tab"
		), $widget->label() . html::etag('span', '.badge error', count($errors))));
		$content .= html::tag('div', array(
			'id' => $name,
			'class' => 'tab-pane' . ($name === $selected_tab ? ' active' : '')
		), $widget_content);
		$widget->content = "";
		$widget->content_children = "";
	}
	?>
	</ul>
	<div class="tab-content"><?php echo $content; ?></div>
</div>
<?php
/* @var $widget Widget */
foreach ($suffix as $widget) {
	echo $widget->render();
}

echo html::tag_close('form');
