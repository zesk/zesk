<?php

global $user;

$x = $this->Object;

$class[] = "video";
if ($this->get('class')) {
	$class[] = $this->class;
}
/* @var $x Video */
?><div class="<?php echo  implode(" ", $class) ?>"><?php
if ($user && $user->can($x, "edit")) {
	?><?php echo  a("/manage/video/edit.php?ID=".$x->id(),img("/share/images/actions/edit.gif"),true) ?><?
}
?>

</div>
