<?php
/**
 * @version $URL$
 * @author $Author: kent $
 * @package {package}
 * @subpackage {subpackage}
 * @copyright Copyright (C) 2016, {company}. All rights reserved.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $route \zesk\Route */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
/* @var $current_user \User */
/* @var $object \zesk\Contact_URL */
?>
<div class="contact-view-url contact-view">
<?php
echo HTML::a($object->value, $object->value);
?> <label><?php
echo $object->label;
?></label>
</div>
