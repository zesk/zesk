<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk \zesk\Kernel */
	
	$application = $this->application;
	/* @var $application \zesk\Application */
	
	$session = $this->session;
	/* @var $session \zesk\Session */
	
	$router = $this->router;
	/* @var $request \zesk\Router */
	
	$request = $this->request;
	/* @var $request \zesk\Request */
	
	$response = $this->response;
	/* @var $response \zesk\Response_Text_HTML */
}

$id = $this->id;
if (empty($id)) {
	$this->id = $id = "datetimepicker-" . $response->id_counter();
}
$value = $this->value;
if (empty($value)) {
	$value = $this->default;
}
$format = $this->allow_times ? 'Y-m-d H:i' : 'Y-m-d';
$this->value = date($format, strtotime($value));
$onchange = $this->onchange;

echo $this->theme('zesk/control/text', array(
	'onchange' => null
));

$options = $this->get(array(
	"lang" => Locale::language(),
	"inline" => false,
	"format" => "Y-m-d H:i"
));
if ($this->data_future_only) {
	$options['minDate'] = Timestamp::now()->format();
} else if ($this->data_past_only) {
	$options['maxDate'] = Timestamp::now()->format();
}
foreach (array(
	"format_time" => "formatTime",
	"format_date" => "formatDate",
	"allow_times" => "allowTimes",
	"step" => "step"
) as $template_key => $js_option) {
	if ($this->has($template_key)) {
		$options[$js_option] = $this->get($template_key);
	}
}

if ($onchange) {
	$onchange = JavaScript::clean_code($onchange);
	$options['*onChangeDateTime'] = "function (dp, \$input) {\n\tvar datetime = new Date(\$input.val());\n\t$onchange;\n}";
}
if ($this->oninit) {
	$oninit = JavaScript::clean_code($this->oninit);
	$options['*onGenerate'] = "function (datetime) {\n\t$oninit;\n}";
}

$this->response->jquery("\$(\"#$id\").datetimepicker(" . JSON::encode($options) . ");");
