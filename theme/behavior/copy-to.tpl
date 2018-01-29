<?php
// behavior/copy-to

// Options:
// - hide_values
// - show_values

/* @var $response zesk\Response */
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
	var
	$this = $(this),
	$target = $('{target}');
	$this.off('keyup.copy-same').on('keyup.copy-same', function () {
		$target.text($this.val());
	});
	$this.blur(function () {
		$this.off('keyup.copy-same');
	});
});
<?php
$response->jquery(map(ob_get_clean(), $map));
