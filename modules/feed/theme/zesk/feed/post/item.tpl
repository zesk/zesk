<?php
namespace zesk;

/* @var $this Template */
/* @var $application ZeroBot */
/* @var $session Session */
/* @var $request Request */
/* @var $response Response */
/* @var $current_user User */
/* @var $object Feed_Post */
$link = $object->link;

$title = $link ? HTML::tag('a', array(
	'href' => $link
), $object->title) : $object->title;
echo HTML::div_open('.feed-item');
echo HTML::tag("h2", $title);
echo $object->description;
echo HTML::div_close();
