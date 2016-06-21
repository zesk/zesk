<?php
$this->response->title(__("Forgotten password email sent"));
?>
<div class="jumbotron">
<?php 
		echo html::tag("h1", __("Email sent"));
		echo html::tag("p", __("Please check your email for a message containing a link, and follow the instructions."));
?>
</div>