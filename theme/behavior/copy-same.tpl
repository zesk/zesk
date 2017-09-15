<?php
// behavior/copy-same

// Options:
// - hide_values
// - show_values

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
	var
	$this = $(this),
	$target = $('{target}'),
	v = $target.val();
	if (v === "" || v === $this.val()) {
		$this.off('keyup.copy-same').on('keyup.copy-same', function () {
			$target.val($this.val());
		});
		$this.blur(function () {
			$this.off('keyup.copy-same');
		});
	}
});
<?php
$response->jquery(map(ob_get_clean(), $map));
