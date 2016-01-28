<?php

/* @var $workflow Workflow */
$workflow = $this->workflow;

echo html::tag_open('ol', '.workflow');
/* @var $step Workflow_Step */
foreach ($this->steps as $step) {
	$completed = $step->completed;
	$classes = $completed ? "complete" : "incomplete";
	$icon = html::tag('div', array(
		"class" => css::add_class('pull-right glyphicon', $completed ? 'glyphicon-ok' : 'glyphicon-remove')
	), "");
	echo html::tag('li', css::add_class('step', $classes), html::div('.step-content', $icon . $step->render()));
}
echo html::tag_close('ol');
