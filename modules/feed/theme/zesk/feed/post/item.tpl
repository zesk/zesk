<?php
if (false) {
	/* @var $this Template */
	
	$application = $this->application;
	/* @var $application ZeroBot */
	
	$session = $this->session;
	/* @var $session Session */
	
	$request = $this->request;
	/* @var $request Request */
	
	$response = $this->response;
	/* @var $response Response_HTML */
	
	$current_user = $this->current_user;
	/* @var $current_user User */
	
	$object = $this->object;
	/* @var $object zesk\Feed_Post */
}

$link = $object->link;

$title = $link ? html::tag('a', array(
	'href' => $link
), $object->title) : $object->title;
echo html::div_open('.feed-item');
echo html::tag("h2", $title);
echo $object->description;
echo html::div_close();