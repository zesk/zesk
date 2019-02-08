<?php
namespace zesk;

/* @var $this \zesk\Template */
/* @var $application \zesk\Application */
/* @var $locale \zesk\Locale */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
$col = $this->column;
$tpl = $col . "-pairs-template";

echo HTML::tag_open("div", ".pairs");
foreach (to_array($this->value) as $k => $v) {
	echo HTML::div(".pair", HTML::input("text", $this->column . "[]", $k) . HTML::input("text", $this->column . "_value[]", $v));
}
echo HTML::a("#", array(
	"id" => $col . "_add",
	"class" => "control-pairs-add",
	"data-template" => $tpl,
), "Add");
echo HTML::tag_close("div");
echo HTML::tag("script", array(
	"id" => $tpl,
	"type" => "text/x-template",
), HTML::div(".pair", HTML::input("text", $this->column . "[]", "") . HTML::input("text", $this->column . "_value[]", "")));

$response->jquery('$("a.control-pairs-add").on("click", function () {
	var $this = $(this); $this.before($("#" + $this.data("template")).html());
});');
