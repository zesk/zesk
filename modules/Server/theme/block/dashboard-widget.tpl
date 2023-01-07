<?php declare(strict_types=1);
/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/* @var $response Response */
$response = $this->response;

$id = $this->id;
if (!$id) {
	$this->id = $id = 'dashboard-widget-server';
}
echo HTML::tag_open('div', [
	'class' => CSS::addClass('dashboard-widget', $this->class),
	'id' => $id,
]);
?>
<div class="header">
	<h2><?php echo $this->title; ?></h2>
</div>
<div class="content">
	<?php echo $this->content?>
</div>
<?php
echo HTML::tag_close('div');
