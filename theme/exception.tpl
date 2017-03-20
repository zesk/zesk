<?php
/**
 * 
 */
namespace zesk;

/* @var $this Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \Session */
/* @var $request Router */
/* @var $request Request */
/* @var $response Response_Text_HTML */
/* @var $current_user \User */

/* @var $exception Exception */
$exception = $this->exception;
$class = get_class($this->exception);

$zesk->logger->error("Exception: {exception_class}\nMessage: {message}\nServer:\n{server}\nRequest:\n{request}\nException: {exception_class}\nBacktrace:\n{backtrace}\n{exception}", array(
	"server" => Text::format_pairs($_SERVER),
	"request" => Text::format_pairs($_REQUEST),
	"exception_class" => $class,
	"exception" => $exception,
	"message" => $exception->getMessage(),
	"backtrace" => $exception->getTraceAsString()
));

$this->begin('body/exception.tpl');

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
	<p>The computer error given was:</p>
	<?php
	echo HTML::tag('code', $message);
	if ($dev) {
		?><p>The call stack is:</p><?php
		echo $this->theme('exception/trace', array(
			"content" => $trace
		));
		?>
	<?php 
	} 
	if ($this->suffix) {
		echo HTML::tag("p", $this->suffix);
	}
	?>
</div>
<?php
echo $this->end();
