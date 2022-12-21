<?php declare(strict_types=1);
namespace zesk;

// behavior/copy-same

// Options:
// - hide_values
// - show_values

/* @var $response Response */
/* @var $widget Widget */
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
$response->html()->jquery(map(ob_get_clean(), $map));
