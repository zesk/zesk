<?php
namespace zesk;

$english = $this->label;
$html = $this->html;
if (!$html && $this->show_size > 0) {
	$english = HTML::ellipsis($english, $this->show_size);
}

$suffix = "";
if (!$this->hide_sort_icon) {
	$icon = !$this->selected ? 'glyphicon-sort' : ($this->ascending ? 'glyphicon-sort-by-attributes ascending' : 'glyphicon-sort-by-attributes-alt descending');
	$suffix = HTML::span(".glyphicon $icon", null) . HTML::etag('.sort-number', $this->sort_number);
}
?>
<div class="sort-widget">
	<?php
	echo HTML::tag('a', array(
		'href' => $this->url,
		"alt" => $this->alt
	), HTML::span("title", $english) . $suffix);
	?>
</div>
