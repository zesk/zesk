<?php
/**
 *
 */
echo $this->theme('zesk/control/text');

if ($this->children) {
	foreach ($this->children as $child) {
		/* $var $child Widget */
		echo $child->render();
	}
}
