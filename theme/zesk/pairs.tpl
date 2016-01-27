<?php

?><table class="pairs"><?php
$pairs = to_array($this->content);
foreach ($pairs as $k => $v) {
	echo html::tag('tr', html::tag('th', $k) . html::tag('td', $v));
}
?></table>