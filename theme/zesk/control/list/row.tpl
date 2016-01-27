<?php
if (is_array($this->row_widgets)) {
	$children = $this->row_widgets;
} else {
	$children = $this->children;
}
echo $this->theme('control/widgets', array(
	"children" => $children
));
