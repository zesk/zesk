<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Template_Test extends Test_Unit {
	public function initialize(): void {
	}

	public function test_begin(): void {
		$this->application->theme_path($this->test_sandbox());

		file_put_contents($this->test_sandbox("good.tpl"), "<?php echo 3.14159;");
		$path = null;
		$options = false;
		$template = new Template($this->application);
		$template->begin("good.tpl");

		$result = $template->end([
			"bad" => 1,
		]);
		$this->assert_equal($result, "3.14159");
	}

	public function test_find_path(): void {
		$template = new Template($this->application);
		$template->find_path("template.tpl");
	}

	public function test_would_exist(): void {
		$path = "foo.tpl";
		$template = new Template($this->application);
		$template->would_exist($path);
	}

	public function test_output(): void {
		$this->application->theme_path($this->test_sandbox());

		$files = [];
		for ($i = 0; $i < 5; $i++) {
			$files[$i] = $f = $this->test_sandbox($i . ".tpl");
			$pushpop = "echo \"PUSH {\\n\" . zesk\\Text::indent(\$application->theme(\"" . ($i + 1) . "\", array(\"i\" => $i)), 1) . \"} POP\\n\";";
			$content = <<<END
<?php
echo "BEGIN zesk\Template $i {\\n";
\$this->v$i = "hello$i";
\$this->g = "hello$i";
echo "v (" . \$this->v0 . "," . \$this->v1 . "," . \$this->v2 . "," . \$this->v3 . "," . \$this->v4 . ")\\n";
echo "g (" . \$this->g. ")\\n";
echo "h (" . \$this->h. ")\\n";
{pushpop}
echo "v (" . \$this->v0 . "," . \$this->v1 . "," . \$this->v2 . "," . \$this->v3 . "," . \$this->v4 . ")\\n";
echo "g (" . \$this->g. ")\\n";
echo "h (" . \$this->h. ")\\n";
\$this->h = "hello$i";
echo "} END zesk\Template $i";
END;

			$map = [
				'pushpop' => ($i !== 4) ? $pushpop : "echo \"LEAF\\n\";\n",
			];

			file_put_contents($f, map($content, $map));
		}

		$path = null;
		$options = [
			"application" => $this->application,
		];
		$x = new Template($this->application, "0.tpl", $options);
		$result = $x->render();

		$expect = <<<EOF
BEGIN zesk\Template 0 {
v (hello0,,,,)
g (hello0)
h ()
PUSH {
	BEGIN zesk\Template 1 {
	v (hello0,hello1,,,)
	g (hello1)
	h ()
	PUSH {
		BEGIN zesk\Template 2 {
		v (hello0,hello1,hello2,,)
		g (hello2)
		h ()
		PUSH {
			BEGIN zesk\Template 3 {
			v (hello0,hello1,hello2,hello3,)
			g (hello3)
			h ()
			PUSH {
				BEGIN zesk\Template 4 {
				v (hello0,hello1,hello2,hello3,hello4)
				g (hello4)
				h ()
				LEAF
				v (hello0,hello1,hello2,hello3,hello4)
				g (hello4)
				h ()
				} END zesk\Template 4
			} POP
			v (hello0,hello1,hello2,hello3,hello4)
			g (hello4)
			h (hello4)
			} END zesk\Template 3
		} POP
		v (hello0,hello1,hello2,hello3,hello4)
		g (hello4)
		h (hello3)
		} END zesk\Template 2
	} POP
	v (hello0,hello1,hello2,hello3,hello4)
	g (hello4)
	h (hello2)
	} END zesk\Template 1
} POP
v (hello0,hello1,hello2,hello3,hello4)
g (hello4)
h (hello1)
} END zesk\Template 0
EOF;
		echo $result;
		$this->assert_equal(trim($result), trim($expect));

		echo basename(__FILE__) . ": success\n";
	}
}
