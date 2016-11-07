<?php
/**
 * 
 */
echo $this->theme('control/text');

if ($this->children) {
	foreach ($this->children as $child) {
		/* $var $child Widget */
		echo $child->render();
	}
}
