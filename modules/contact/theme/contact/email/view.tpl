<?php
/**
 * @version $URL$
 * @author $Author: kent $
 * @package contact
 * @subpackage theme
 * @copyright Copyright (C) 2016, Market Acumen, Inc. All rights reserved.
 */
namespace zesk;

if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk \zesk\Kernel */
	
	$application = $this->application;
	/* @var $application zesk\Application */
	
	$session = $this->session;
	/* @var $session \zesk\Session */
	
	$router = $this->router;
	/* @var $request \zesk\Router */
	
	$request = $this->request;
	/* @var $request \zesk\Request */
	
	$response = $this->response;
	/* @var $response \zesk\Response_Text_HTML */
	
	$current_user = $this->current_user;
	/* @var $current_user User */
	
	$object = $this->object;
	/* @var $object \Contact_Email */
}
?>
<div class="contact-view-email contact-view">
<?php echo HTML::a('mailto:'.$object->value, $object->value); ?> <label><?php echo $object->label; ?></label>
</div>
