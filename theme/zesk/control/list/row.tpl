<?php
if ($this->row_widget instanceof Widget) {
	$children = $this->row_widget->children;
} else if (is_array($this->row_widgets)) {
	$children = $this->row_widgets;
} else {
	$children = $this->children;
}
echo $this->theme('zesk/control/widgets', array(
	"children" => $children
));
