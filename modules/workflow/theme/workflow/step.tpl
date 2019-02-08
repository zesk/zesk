<?php
use zesk\HTML;

/* @var $workflow Workflow */
$workflow = $this->workflow;
/* @var $step Workflow_Step */
$step = $this->step;

$title = $step->title;
if ($step->href) {
	$title = HTML::tag('a', array(
		'href' => $step->href,
	), $title);
}
echo HTML::tag('h2', $title) . HTML::etag("p", $step->description);
