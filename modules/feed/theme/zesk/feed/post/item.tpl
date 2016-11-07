<?php
if (false) {
	/* @var $this zesk\Template */
	
	$application = $this->application;
	/* @var $application ZeroBot */
	
	$session = $this->session;
	/* @var $session Session */
	
	$request = $this->request;
	/* @var $request Request */
	
	$response = $this->response;
	/* @var $response zesk\Response_Text_HTML */
	
	$current_user = $this->current_user;
	/* @var $current_user User */
	
	$object = $this->object;
	/* @var $object zesk\Feed_Post */
}

$link = $object->link;

$title = $link ? HTML::tag('a', array(
	'href' => $link
), $object->title) : $object->title;
echo HTML::div_open('.feed-item');
echo HTML::tag("h2", $title);
echo $object->description;
echo HTML::div_close();
