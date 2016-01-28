<?php
class Markdown_Test extends Test_Unit {

	function initialize() {
		Module::load('diff');
	}
	function test_md() {
		Module::load('markdown');

		$failed = false;

		$tests = dir::ls(dirname(__FILE__), '/.markdown$/');
		chdir(dirname(__FILE__));
		$bar = "\n" . str_repeat("*", 80) . "\n";
		foreach ($tests as $test) {
			echo "Processing $test ... ";
			list($markdown, $html) = explode("\n-markdown-\n", file_get_contents($test));
			if (strpos($markdown, "***SKIP***") !== false) {
				// TODO
				continue;
			}
			$result_html = Markdown::filter($markdown);
			$diff = new Diff_Lines($html, $result_html, true);
			if (!$diff->is_identical()) {
				echo "FAILED\n";
				echo $bar;
				echo "<Test File, >Computed Result\n";
				//		echo $result_html;
				echo $diff->output();
				file_put_contents($test . ".loaded", $html);
				file_put_contents($test . ".computed", $result_html);
				echo "bbdiff $test.loaded $test.computed\n";
				echo $bar;
				$failed = true;
				break;
			} else {
				echo "SUCCESS\n";
			}
		}
		$text = <<<EOF

This is __bold__.

- List item 1
- List item 2
- List item 3

This is `<pre>` tag:

What else can we do?

Heading 1
=========
My heading

Heading 2
---------
Hello heading

Email me: [Kent](mailto:kent@marketacumen.com)

Or link to: http://zesk.com/

EOF;

		Markdown::filter($text);

		if ($failed) {
			exit(1);
		}
	}
}