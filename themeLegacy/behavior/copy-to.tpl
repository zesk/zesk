<?php declare(strict_types=1);
namespace zesk;

// behavior/copy-to

// Options:
// - hide_values
// - show_values

/* @var $response zesk\Response */
/* @var $widget Widget */
/* @var $object Model */
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
$response->html()->jquery(map(ob_get_clean(), $map));
