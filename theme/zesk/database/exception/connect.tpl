<?php
namespace zesk;

$this->begin('body/exception');
?>
<div class="error">
	<p>
		There was a problem connecting to your database <?php echo HTML::tag('em', "$this->database") ?>
		at <em><?php echo $this->host; ?></em>, connecting as user <?php echo HTML::tag('em', "".$this->user); ?>. Please check:</p>
	<ul>
		<li><a href="http://zesk.com/database-exception-connect#running">That
				it's running.</a></li>
		<li><a href="http://zesk.com/database-exception-connect#credentials">That
				the access credentials are correctly configured.</a></li>
	</ul>
	<?php if ($this->application->development()) { ?>
	<p>The computer error given was:</p>
	<?php echo HTML::tag('code', $this->message); ?>
	<?php } ?>
</div>
<?php
echo $this->end();
