<?php
namespace zesk;

$col = $this->column;
$tpl = $col . "-pairs-template";

echo HTML::tag_open("div", ".pairs");
foreach (to_array($this->value) as $k => $v) {
	echo HTML::div(".pair", HTML::input("text", $this->column . "[]", $k) . HTML::input("text", $this->column . "_value[]", $v));
}
echo HTML::a("#", array(
	"id" => $col . "_add",
	"class" => "control-pairs-add",
	"data-template" => $tpl
), "Add");
echo HTML::tag_close("div");
echo HTML::tag("script", array(
	"id" => $tpl,
	"type" => "text/x-template"
), HTML::div(".pair", HTML::input("text", $this->column . "[]", "") . HTML::input("text", $this->column . "_value[]", "")));

$this->response->jquery('$("a.control-pairs-add").on("click", function () {
	var $this = $(this); $this.before($("#" + $this.data("template")).html());
});');
