<?php
global $user;

$x = $this->Object;
/* @var $x FileGroup */

?><div class="link-group">
<h1><?php echo  $x->Name ?></h1><?
echo $this->theme('control/admin-edit');
?><?php echo  etag("p", array("class" => "intro"), $x->Body) ?>
<?php echo  $x->output("file-list") ?>
</div>
