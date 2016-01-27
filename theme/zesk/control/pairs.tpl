<?php

$col = $this->column;
$tpl = $col . "-pairs-template";

echo html::tag_open("div", ".pairs");
foreach (to_array($this->value) as $k => $v) {
	echo html::div(".pair", html::input("text", $this->column . "[]", $k) . html::input("text", $this->column . "_value[]", $v));
}
echo html::a("#", array(
	"id" => $col . "_add",
	"class" => "control-pairs-add",
	"data-template" => $tpl
), "Add");
echo html::tag_close("div");
echo html::tag("script", array(
	"id" => $tpl,
	"type" => "text/x-template"
), html::div(".pair", html::input("text", $this->column . "[]", "") . html::input("text", $this->column . "_value[]", "")));

$this->response->jquery('$("a.control-pairs-add").on("click", function () {
	var $this = $(this); $this.before($("#" + $this.data("template")).html());
});');
