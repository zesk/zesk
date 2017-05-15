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

$value = Timestamp::factory($value)->date()->format($this->get('format', '{YYYY}-{MM}-{DD}'));
echo $this->theme('zesk/control/text', array(
	'value' => $value
));

$options = $this->get(array(
	"lang" => Locale::language(Locale::current()),
	"inline" => $this->getb('inline'),
	"format" => "Y-m-d",
	'timepicker' => false
));
if ($this->data_future_only) {
	$options['minDate'] = Timestamp::now()->format();
} else if ($this->data_past_only) {
	$options['maxDate'] = Timestamp::now()->format();
}

$this->response->jquery("\$(\"#$id\").datetimepicker(" . JSON::encode($options) . ");");
