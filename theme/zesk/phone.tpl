<?php

$phone = $this->content;
$digits = preg_replace('/[^0-9]/', '', $phone);
if (strlen($digits) === 10) {
	echo '(' . substr($digits, 0, 3) . ')' . substr($digits, 3, 3) . "-" . substr($digits, 6);
} else {
	echo $phone;
}