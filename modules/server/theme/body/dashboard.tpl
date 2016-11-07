<?php
$menu = array(
	"logout" => "Logout"
);
echo $this->theme("bootstrap/navbar", array(
	"title" => php_uname("n"),
	"menu" => $menu
));
foreach ($this->widgets as $widget) {
	echo $this->theme("dashboard/$widget");
}
