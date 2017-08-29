<?php
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
	function initialize() {
		Template::clear_cache();
	}
	function test_begin() {
		$path = null;
		$options = false;
		Template::begin($path, $options);
	}
	function test_end() {
		$options = array();
		$content_variable = 'content';
		Template::end($options, $content_variable);
	}
	function test_find_path() {
		Template::find_path("template.tpl");
	}
	function test_instance() {
		$path = null;
		$options = false;
		Template::instance($path, $options);
	}
	function test_run() {
		$path = null;
		$options = false;
		$default = null;
		Template::run($path, $options, $default);
	}
	function test_would_exist() {
		$path = null;
		Template::would_exist($path);
	}
	function test_output() {
		$this->application->theme_path($this->test_sandbox());
		
		$files = array();
		for ($i = 0; $i < 5; $i++) {
			$files[$i] = $f = $this->test_sandbox($i . ".tpl");
			$pushpop = "echo \"PUSH {\\n\" . Text::indent(Template::instance(\"" . ($i + 1) . ".tpl\", array(\"i\" => $i)), 1) . \"} POP\\n\";";
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
			
			$map = array(
				'pushpop' => ($i !== 4) ? $pushpop : "echo \"LEAF\\n\";\n"
			);
			
			file_put_contents($f, map($content, $map));
		}
		
		$path = null;
		$options = false;
		$x = new Template("0.tpl", $options);
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
