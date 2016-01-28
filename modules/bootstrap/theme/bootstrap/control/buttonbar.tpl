<?php
	
?>
<div class="form-actions">
<?php

/*
 * @var $this Template @var $object Model @var $widget Widget
 */
$this->widget->content_children = "";
foreach ($this->widget->children as $widget) {
	echo $widget->content;
}
?></div>