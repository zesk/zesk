<?php declare(strict_types=1);
?>
<div class="form-actions">
<?php

/*
 * @var $this zesk\Template @var $object Model @var $widget Widget
 */
$this->widget->content_children = "";
foreach ($this->widget->children as $widget) {
	echo $widget->content;
}
?></div>
