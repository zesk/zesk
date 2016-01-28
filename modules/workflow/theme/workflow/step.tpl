<?php

/* @var $workflow Workflow */
$workflow = $this->workflow;
/* @var $step Workflow_Step */
$step = $this->step;

$title = $step->title;
if ($step->href) {
	$title = html::tag('a', array(
		'href' => $step->href
	), $title);
}
echo html::tag('h2', $title) . html::etag("p", $step->description);