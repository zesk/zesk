<?php
// behavior/copy-value

/* @var $response zesk\Response_Text_HTML */
$response = $this->response;
/* @var $widget Widget */
$widget = $this->widget;
/* @var $object Model */
$object = $this->object;

$id = $widget->id();

$map['id'] = "#$id";
$map['target'] = $this->target;

ob_start();
?>
$('{id}').focus(function () {
	var $this = $(this);
	$this.off('keyup.copy-value').on('keyup.copy-value', function () {
		$('{target}').text($this.val());
	}).off('blur.copy-value').on('blur.copy-value', function () {
		$this.off('keyup.copy-value');
		$this.off('blur.copy-value');
	});
});
<?php
$response->jquery(map(ob_get_clean(), $map));
