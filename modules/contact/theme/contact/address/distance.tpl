<?php
if (false) {
	/* @var $this Template */
	
	$application = $this->application;
	/* @var $application Application */
	
	$session = $this->session;
	/* @var $session Session */
	
	$request = $this->request;
	/* @var $request Request */
	
	$response = $this->response;
	/* @var $response Response_HTML */
	
	$object = $this->object;
	/* @var $object Contact_Address */
	
	/* @var $from Contact_Address */
	$from = $this->form;
}
if (!$this->from instanceof Contact_Address) {
	return;
}
$distance = $object->distance($this->from);
if ($distance !== null) {
	?><span class="contact-address-distance"><?php
	echo $this->theme('distance', array(
		'content' => $distance,
		'units' => 'miles'
	));
	?></span><?php
}
