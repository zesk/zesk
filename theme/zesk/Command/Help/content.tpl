<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

use zesk\Locale\Locale;

/* @var $this Theme */
/* @var $locale Locale */
/* @var $application Application */

$locale = $application->locale;
?># zesk Management

Usage: zesk [configuration-opts] [ command command-args command1 command1-args ... ]

Zesk command-line management interface. Execute commands within an application context.

These options are used to configure the environment BEFORE the application context is loaded (typically a file which matches *.application.php) found in any directory above the current command when run.

configuration-opts:

    --set name=value   Set the global to value (use :: to specify paths)
    --name=value       Set the global to value
    --unset name       Unset a global value
    --cd dir           Change to this directory
    --search dir       If supplied, search here for application files, may be supplied more than once to search multiple places in order. Otherwise, uses the current directory. (see --cd)
    --config file      Load a configuration file as globals

command may be:

    - A file (starting with a dot or slash) to include directly (no additional arguments)
    - a command shortcut to invoke which may take one or more additional arguments

Commands are processed until the first non-zero exit code (or unhandled Exception or Error) at which point execution terminates and the process exits.

Commands which take a variable number of arguments can be chained by using `--` to stop processing of arguments

<?php
$tab = '    ';
foreach ($this->getArray('categories') as $category => $commands) {
	echo "== $category " . str_repeat('=', 80 - strlen($category)) . "\n";
	foreach ($commands as $command => $info) {
		$extras = '';
		if (array_key_exists('shortcuts', $info)) {
			$extras = ' (' . implode(', ', toArray($info['shortcuts'])) . ')';
		}
		echo "$command$extras\n";
		$desc = $info['desc'] ?? $locale('No description provided.');
		$parameters = [];
		// // 		foreach ($info['global'] ?? array() as $global => $foo) {
		// // 			$parameters[] = "$global (" . $foo[0] . ")" . (count($foo) > 1 ? "\n" . Text::indent($foo[2] ?? null, 1, true, $tab) : "");
		// // 		}
		$parameters = implode("\n", $parameters);
		if ($parameters) {
			$parameters = "$tab$tab" . $locale('Globals:') . "\n" . Text::indent($parameters, 3, false, $tab) . "\n";
		}
		$desc .= "\n\n";
		echo rtrim(Text::indent($desc, 1, true, $tab)) . "\n$parameters";
	}
	echo "\n";
}
echo "To get command-specific options: zesk command --help\n";
