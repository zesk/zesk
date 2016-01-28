<?php
/* @var $response Response_HTML */
$response = $this->response;

$id = $this->id;
if (!$id) {
	$this->id = $id = "dashboard-widget-" . $response->id_counter();
}
echo html::tag_open("div", array(
	"class" => css::add_class("dashboard-widget", $this->class), 
	"id" => $id
));
?>
<div class="header">
	<h2><?php echo $this->title; ?></h2>
</div>
<div class="content">
	<?php echo $this->content?>
</div>
<?php
echo html::tag_close("div");
