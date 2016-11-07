<?php
/* @var $this zesk\Template */
$parent = $this->parent;
if (!$parent) {
	echo $this->empty_string;
	return;
}
$errors = $parent->errors();
if (count($errors) === 0) {
	$errors = $parent->children_errors();
}
if (count($errors) === 0) {
	echo $this->empty_string;
	return;
}
?><div class="alert alert-error">
	<a class="close" data-dismiss="alert" href="#">&times;</a><?php
	echo $this->theme(zesk::root('theme/zesk/view/errors'), $this->variables);
	?></div>
