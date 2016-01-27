<?php
$this->begin('body/exception.tpl');

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

?>
<div class="error">
	<p>There was a database error.</p>
	<?php
	if ($this->application->development()) {
		?><p>The computer error given was:</p><?php
		echo html::tag('code', $exception->getMessage());
		?><p>The call stack is:</p><?php
		echo $this->theme('exception/trace', array(
			"content" => $exception->getTrace()
		));
	}
	?>
</div>
<?php
echo $this->end();
