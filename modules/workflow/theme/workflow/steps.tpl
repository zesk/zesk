<?php declare(strict_types=1);
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

use \Workflow;

/* @var $workflow Workflow */
$workflow = $this->workflow;

echo HTML::tag_open('ol', '.workflow');
/* @var $step Workflow_Step */
foreach ($this->steps as $step) {
	$completed = $step->completed;
	$classes = $completed ? "complete" : "incomplete";
	$icon = HTML::tag('div', [
		"class" => CSS::add_class('pull-right glyphicon', $completed ? 'glyphicon-ok' : 'glyphicon-remove'),
	], "");
	echo HTML::tag('li', CSS::add_class('step', $classes), HTML::div('.step-content', $icon . $step->render()));
}
echo HTML::tag_close('ol');
