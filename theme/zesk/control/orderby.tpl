<?php
$english = $this->label;
$html = $this->html;
if (!$html && $this->show_size > 0) {
	$english = html::ellipsis($english, $this->show_size);
}

$icon = !$this->selected ? 'glyphicon-sort' : ($this->ascending ? 'glyphicon-sort-by-attributes ascending' : 'glyphicon-sort-by-attributes-alt descending');
?>
<div class="sort-widget">
	<?php
	echo html::tag('a', array(
		'href' => $this->url,
		"alt" => $this->alt
	), html::span("title", $english) . html::span(".glyphicon $icon", null) . html::etag('.sort-number', $this->sort_number));
	?>
</div>
