<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/modules/developer/theme/developer/forbidden.tpl $
 * @author $Author: kent $
 * @package {package}
 * @subpackage {subpackage}
 * @copyright Copyright (C) 2016, {company}. All rights reserved.
 */
/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
/* @var $current_user \zesk\User */
?>
<h1>Forbidden</h1>
<p>While under development, this site is not accessible to the outside
	world.</p>
<p class="tiny"><?php echo $request->ip(); ?></p>
