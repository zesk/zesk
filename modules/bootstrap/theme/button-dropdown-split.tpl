<?php
namespace zesk;

$style = $this->style || $this->arg2;
if ($style && !begins($style, "btn-")) {
	$style = "btn-";
}
?>
<div class="<?php echo HTML::tag_class("btn-group", $style); ?>">
	<a class="btn" href="#"><?php echo $this->content; ?></a> <a
		class="btn dropdown-toggle" data-toggle="dropdown" href="#"> <span
		class="caret"></span>
	</a>
	<ul class="dropdown-menu">
    <?php echo $this->links || $this->arg1; ?>
  </ul>
</div>
