<?php declare(strict_types=1);
/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

/* @var $this \zesk\Template */
/* @var $locale \zesk\Locale */
/* @var $application \zesk\Application */
/* @var $session \zesk\Session */
/* @var $router \zesk\Router */
/* @var $request \zesk\Request */
/* @var $response \zesk\Response */
$configuration = $application->configuration;

echo "<?php\n";

$php = new PHP();
$t = $php->indent_char = $this->get('indent_char', $configuration->path_get('Text::indent_char', "\t"));
$php->indent_multiple = to_integer($this->getInt('indent_multiple', $configuration->path_get('Text::indent_multiple')), 1);

ob_start();

echo 'class ' . $this->class_name . " extends ORM_Schema {\n";
echo $t . "function schema() {\n";
echo $t . $t . 'return ' . ltrim($php->render($this->schema, 2)) . ";\n";
echo $t . "}\n";
echo "}\n";
