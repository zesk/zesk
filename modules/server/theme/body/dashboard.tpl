<?php declare(strict_types=1);
$cache_item = [
	"logout" => "Logout",
];
echo $this->theme("bootstrap/navbar", [
	"title" => php_uname("n"),
	"menu" => $cache_item,
]);
foreach ($this->widgets as $widget) {
	echo $this->theme("dashboard/$widget");
}
