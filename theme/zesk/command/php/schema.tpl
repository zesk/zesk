<?php

echo "<?php\n";

$php = new php();
$php->indent_char = $this->get("indent_char", zesk::get("indent_char", "\t"));
$php->indent_multiple = $this->get("indent_multiple", zesk::get("indent_multiple", 1));

ob_start();

// TODO Indent below to inherit settings above
?>
class <?php echo $this->class_name; ?> extends Database_Schema {
	function schema() {
		return <?php echo ltrim($php->render($this->schema, 2)); ?>;
	}
}
