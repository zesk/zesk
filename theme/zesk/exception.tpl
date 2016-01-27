<?php

/* @var $exception Exception */
$exception = $this->exception;
$class = get_class($this->exception);

log::error("Exception: {exception_class}\nMessage: {message}\nServer:\n{server}\nRequest:\n{request}\nException: {exception_class}\nBacktrace:\n{backtrace}\n{exception}", array(
	"server" => text::format_pairs($_SERVER),
	"request" => text::format_pairs($_REQUEST),
	"exception_class" => $class,
	"exception" => $exception,
	"message" => $exception->getMessage(),
	"backtrace" => $exception->getTraceAsString()
));

$this->begin('body/exception');

if (!$exception instanceof Exception) {
	$message = "Not an exception: " . type($exception);
	$trace = debug_backtrace();
} else {
	$message = $exception->getMessage();
	$trace = $exception->getTrace();
}
$dev = $this->application->development();
?>
<div class="exception-error">
	<h1><?php echo $dev ? $class : strtr($class, "_", " ")?>
		<!--  <?php echo $class; ?> -->
	</h1>
	<?php if ($dev) { ?>
		<p>The computer error given was:</p>
		<?php
		echo html::tag('code', $message);
		?><p>The call stack is:</p><?php
		echo $this->theme('exception/trace', array(
			"content" => $trace
		));
		?>
	<?php } ?>
</div>
<?php
echo $this->end();
