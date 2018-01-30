<?php
namespace zesk;

// behavior/copy-value

/* @var $response Response */
/* @var $widget Widget */
/* @var $object Model */
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
$response->html()->jquery(map(ob_get_clean(), $map));
