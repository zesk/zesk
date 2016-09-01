<?php
if (false) {
	/* @var $this Template */
	
	$zesk = $this->zesk;
	/* @var $zesk zesk\Kernel */
	
	$application = $this->application;
	/* @var $application TimeBank */
	
	$session = $this->session;
	/* @var $session Session */
	
	$router = $this->router;
	/* @var $request Router */
	
	$request = $this->request;
	/* @var $request Request */
	
	$response = $this->response;
	/* @var $response Response_HTML */
}
$configuration = $zesk->configuration;

echo "<?php\n";

$php = new php();
$php->indent_char = $this->get("indent_char", $configuration->path_get("text::indent_char", "\t"));
$php->indent_multiple = to_integer($this->geti("indent_multiple", $configuration->path_get("text::indent_multiple")), 1);

ob_start();

// TODO Indent below to inherit settings above
?>
class <?php echo $this->class_name; ?> extends Database_Schema {
	function schema() {
		return <?php echo ltrim($php->render($this->schema, 2)); ?>;
	}
}
