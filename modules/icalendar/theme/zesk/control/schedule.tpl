<?php
/**
 * @see zesk\Control_Schedule
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user User */
/* @var $object ORM */
/* @var $widget_unit Control_Dropdown */
/* @var $widget_dayofweek Control_Dropdown */
/* @var $widget_dayofmonth Control_Dropdown */
/* @var $widget_hourofday Control_Dropdown */
?><div class="input-group"><?php
echo $widget_unit->render();
echo $widget_dayofweek->render();
echo $widget_dayofmonth->render();
echo $widget_hourofday->render();
?>
</div>
