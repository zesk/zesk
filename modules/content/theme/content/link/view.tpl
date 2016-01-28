<?php
global $user;

$x = $this->Object;

$class[] = "link";
if ($this->get('class')) {
	$class[] = $this->class;
}
$rd_link = url::query_format("/out", array("Link" => $x->id(), "url" => $x->URL, "key" => md5($x->URL . _G('url_key'))));

/* @var $x Link */
?><div class="<?php echo  implode(" ", $class) ?>"><?php echo  etag("a", array("href" => $rd_link, "onmouseover" => "window.status='".$x->URL."'", "onmouseout" => "window.status=''"), $x->image()) ?>
<?php echo  aa($rd_link, array("class" => "title", "onmouseover" => "window.status='".$x->URL."'", "onmouseout" => "window.status=''"), $x->Name) ?><?
echo $this->theme('control/admin-edit');
?><?php echo  etag("p", array("class" => "desc"), $x->Body) ?>

</div>
