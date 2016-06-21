<?php
/**
 * @see zesk\Control_Schedule
 */
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
	/* @var $object Object */
	
	/* @var $widget_unit Control_DropDown */
	$widget_unit = $this->widget_unit;
	
	/* @var $widget_dayofmonth Control_DropDown */
	$widget_dayofmonth = $this->widget_dayofmonth;
	
	/* @var $widget_dayofmonth Control_DropDown */
	$widget_dayofweek = $this->widget_dayofweek;
	
	/* @var $widget_hourofday Control_DropDown */
	$widget_hourofday = $this->widget_hourofday;
}

?><div class="input-group"><?php

echo $widget_unit->render();
echo $widget_dayofweek->render();
echo $widget_dayofmonth->render();
echo $widget_hourofday->render();

?>
</div>
<!-- /input-group -->
