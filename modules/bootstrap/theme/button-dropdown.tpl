<?php

namespace zesk;

$style = $this->style;
if ($style && !begins($style, "btn-")) {
	$style = "btn-";
}
?>
<div class="<?php echo HTML::tag_class("btn-group", $style) ?>">
  <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
	<?php echo $this->content ?>
    <span class="caret"></span>
  </a>
  <ul class="dropdown-menu">
    <?php echo $this->links ?>
  </ul>
</div>
