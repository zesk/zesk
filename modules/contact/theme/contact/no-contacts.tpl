<?php
/* @var $router Router */
$router = $this->router;
?>
<p class="error">You do not have any contacts yet. <?php echo HTML::a($router->get_route("new", "Contact"), "Create a contact");
