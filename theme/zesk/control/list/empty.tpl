<?php
if (!$this->request) {
	$request = $this->request = $this->application->request();
}
?>
<div class="jumbotron">
<?php
echo html::tag("h1", __("No matches"));
echo html::tag("p", __("Try being less specific."));
echo html::tag("p", html::tag("a", array(
	"class" => "btn btn-primary btn-lg",
	"role" => "button",
	"href" => $request->path()
), __("Try again")));
?>
</div>