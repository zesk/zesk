<?php

?><table class="pairs"><?php
$pairs = to_array($this->content);
foreach ($pairs as $k => $v) {
	echo HTML::tag('tr', HTML::tag('th', $k) . HTML::tag('td', $v));
}
?></table>
