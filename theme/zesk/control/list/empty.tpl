<?php
if (!$this->request) {
	$request = $this->request = $this->application->request();
}
?>
<div class="jumbotron">
<?php
echo HTML::tag("h1", __("No matches"));
echo HTML::tag("p", __("Try being less specific."));
echo HTML::tag("p", HTML::tag("a", array(
	"class" => "btn btn-primary btn-lg",
	"role" => "button",
	"href" => $request->path()
), __("Try again")));
?>
</div>
