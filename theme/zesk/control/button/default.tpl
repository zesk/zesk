<?php

echo html::tag("button", array(
	"type" => "button",
	"id" => $this->id,
	"title" => $this->title,
	"class" => css::add_class("btn btn-default", $this->confirm ? "confirm" : "")
) + $this->widget->data_attributes(), html::tag('span', '.glyphicon .glyphicon-repeat', '') . ' ' . $this->button_label);

/* $var $response Response_HTML */
$response = $this->response;

ob_start();
?>
<script>
$('#{id}').off('click.default').on('click.default', function () {
	var $this = $(this), $form = $this.parents('form');
	$(':input', $form).each(function () {
		var $this = $(this), default_value = $this.data('default');
		if (default_value) {
			$this.val(default_value).triggerHandler("default");
		}
	});
	zesk.message("Defaults have been restored.");
});
</script>
<?php
$response->jquery(map(html::extract_tag_contents('script', ob_get_clean()), array(
	'id' => $this->id
)));

