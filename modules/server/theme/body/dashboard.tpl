<?php
$cache_item = array(
	"logout" => "Logout",
);
echo $this->theme("bootstrap/navbar", array(
	"title" => php_uname("n"),
	"menu" => $cache_item,
));
foreach ($this->widgets as $widget) {
	echo $this->theme("dashboard/$widget");
}
