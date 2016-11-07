<?php
/**
 * @see zesk\Control_Schedule
 */
/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \User */
/* @var $object \Object */
/* @var $widget_unit Control_DropDown */
/* @var $widget_dayofweek Control_DropDown */
/* @var $widget_dayofmonth Control_DropDown */
/* @var $widget_hourofday Control_DropDown */
?><div class="input-group"><?php
echo $widget_unit->render();
echo $widget_dayofweek->render();
echo $widget_dayofmonth->render();
echo $widget_hourofday->render();
?>
</div>
