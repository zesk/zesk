<?php
/**
 * @copyright &copy; 2016 Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $zesk \zesk\Kernel */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response_Text_HTML */
$configuration = $zesk->configuration;

echo "<?php\n";

$php = new php();
$php->indent_char = $this->get("indent_char", $configuration->path_get("Text::indent_char", "\t"));
$php->indent_multiple = to_integer($this->geti("indent_multiple", $configuration->path_get("Text::indent_multiple")), 1);

ob_start();

// TODO Indent below to inherit settings above
?>
class <?php echo $this->class_name; ?> extends Database_Schema {
	function schema() {
		return <?php echo ltrim($php->render($this->schema, 2)); ?>;
	}
}
