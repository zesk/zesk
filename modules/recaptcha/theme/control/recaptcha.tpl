<?php declare(strict_types=1);
namespace zesk;

/* @var $response Response */
$response->javascript("https://www.google.com/recaptcha/api.js?onloadCallback=recaptcha_onload", null, [
	"cache" => false,
]);

if (!$this->site_key) {
	echo "<!-- reCAPTCHA site_key is missing -->";
} elseif (!$this->verified) {
	echo HTML::div([
		'class' => 'g-recaptcha',
		'data-sitekey' => $this->site_key,
	], "");
}
if ($this->reverify) {
	$response->javascript_inline("function recaptcha_onload() { recaptcha.reset(); }");
} else {
	$response->javascript_inline("function recaptcha_onload() { }");
}
echo $this->verified_html;
