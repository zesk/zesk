<?php declare(strict_types=1);
namespace zesk;

$this->response->title(__("Forgotten password email sent"));
?>
<div class="jumbotron">
<?php
echo HTML::tag("h1", __("Email sent"));
echo HTML::tag("p", __("Please check your email for a message containing a link, and follow the instructions."));
?>
</div>
