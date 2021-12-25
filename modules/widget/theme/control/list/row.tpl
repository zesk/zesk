<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 */
if ($this->row_widget instanceof Widget) {
	$children = $this->row_widget->children;
} elseif (is_array($this->row_widgets)) {
	$children = $this->row_widgets;
} else {
	$children = $this->children;
}
echo $this->theme('zesk/control/widgets', [
	"children" => $children,
]);
