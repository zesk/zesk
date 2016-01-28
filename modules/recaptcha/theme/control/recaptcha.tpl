<?php
if (false) {
	$response = $this->response;
	/* @var $response Response_HTML */
}

$response->javascript("https://www.google.com/recaptcha/api.js?onloadCallback=recaptcha_onload", null, array(
	"cache" => false
));

if (!$this->site_key) {
	echo "<!-- reCAPTCHA site_key is missing -->";
} else if (!$this->verified) {
	echo html::div(array(
		'class' => 'g-recaptcha',
		'data-sitekey' => $this->site_key
	), "");
}
if ($this->reverify) {
	$response->javascript_inline("function recaptcha_onload() { recaptcha.reset(); }");
} else {
	$response->javascript_inline("function recaptcha_onload() { }");
}
echo $this->verified_html;
