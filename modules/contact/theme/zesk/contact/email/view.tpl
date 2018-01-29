<?php
/**
 * @version $URL$
 * @author $Author: kent $
 * @package contact
 * @subpackage theme
 * @copyright Copyright (C) 2016, Market Acumen, Inc. All rights reserved.
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
/* @var $current_user \User */
/* @var $object \zesk\Contact_Email */
?>
<div class="contact-view-email contact-view">
<?php
echo HTML::a('mailto:' . $object->value, $object->value);
?> <label><?php echo $object->label; ?></label>
</div>
