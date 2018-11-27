<?php

/**
 *
 */
namespace zesk;

/**
 * This help.
 *
 * @category Documentation
 */
class Command_Help extends Command_Base {
    protected $option_types = array(
        'no-core' => 'boolean',
    );

    protected $option_help = array(
        'no-core' => 'Skip all Zesk core commands',
    );

    /**
     *
     * @var array
     */
    private $categories = array();

    /**
     *
     * @var array
     */
    private $aliases = array();

    private $command_paths = array();

    public function run() {
        $this->collect_help();
        echo $this->application->theme("command/help", array(
            "categories" => $this->categories,
            "aliases" => $this->aliases,
        ));
        $this->save_aliases($this->aliases);
        return 0;
    }

    /**
     */
    public function collect_command_files() {
        $zesk = $this->zesk;
        $command_files = array();
        $opts = array(
            'rules_file' => array(
                '/.*\.(inc|php)$/' => true,
                false,
            ),
            'rules_directory_walk' => array(
                '#/\..*$#' => false,
                true,
            ),
            'rules_directory' => false,
        );
        $zesk_root = $this->application->zesk_home();
        $nocore = $this->option_bool("no-core");
        foreach ($this->application->zesk_command_path() as $path => $prefix) {
            $this->command_paths[] = $path;
            if ($nocore && begins($path, $zesk_root)) {
                continue;
            }
            $commands = Directory::list_recursive($path, $opts);
            if ($commands) {
                $command_files[$path] = array(
                    $prefix,
                    ArrayTools::ltrim($commands, "./"),
                );
            }
        }
        return $command_files;
    }

    public function load_commands(array $command_files) {
        $declared_classes_before = ArrayTools::flip_assign(get_declared_classes(), true);
        foreach ($command_files as $path => $structure) {
            list($prefix, $commands) = $structure;
            foreach ($commands as $command) {
                $this->verbose_log("Scanning {command}", compact("command"));
                $command_file = path($path, $command);
                if (strcasecmp($command_file, __FILE__) === 0) {
                    continue;
                }

                try {
                    $this->verbose_log("Including $command_file");
                    require_once $command_file;
                } catch (\Exception $e) {
                    $this->error("Error processing {command_file}: {exception}", array(
                        "exception" => $e->getMessage(),
                        "command_file" => $command_file,
                    ));
                }
            }
        }
        $declared_classes_after = get_declared_classes();
        foreach ($declared_classes_after as $class) {
            if (array_key_exists($class, $declared_classes_before)) {
                continue;
            }
            $this->verbose_log("Registering $class");
            $this->application->classes->register($class);
        }
    }

    public function process_class($class) {
        $this->verbose_log("Checking $class");

        try {
            $refl = new \ReflectionClass($class);
        } catch (Exception_Class_NotFound $e) {
            $this->verbose_log("{class} can not be loaded, skipping", array(
                "class" => $class,
            ));
            return;
        }
        if ($refl->isAbstract()) {
            $this->verbose_log("{class} is abstract, skipping", array(
                "class" => $class,
            ));
            return;
        }
        $command_file = $refl->getFileName();
        $command = StringTools::unprefix($command_file, $this->command_paths);
        $command = File::extension_change(ltrim($command, "/"), null);
        $command = strtr($command, "/", "-");
        $doccomment = $refl->getDocComment();
        $doccomment = DocComment::instance($doccomment)->variables();
        if (array_key_exists('ignore', $doccomment)) {
            return;
        }
        if (array_key_exists('aliases', $doccomment)) {
            foreach (to_list($doccomment['aliases'], array(), " ") as $alias) {
                if (array_key_exists($alias, $this->aliases)) {
                    $this->application->logger->warning("Identical aliases exist for command {0} and {1}: {2}, only {0} will be honored", array(
                        $command,
                        $this->aliases[$alias],
                        $alias,
                    ));
                } else {
                    $this->aliases[$alias] = $command;
                    $this->verbose_log("Alias for `zesk {command}` is `zesk {alias}`", array(
                        "command" => $command,
                        "alias" => $alias,
                    ));
                }
            }
        }
        $doccomment['command'] = $command;
        $doccomment['command_file'] = $command_file;
        $category = avalue($doccomment, 'category', 'Miscellaneous');
        $this->categories[$category][$command] = $doccomment;
    }

    public function collect_help() {
        $command_files = $this->collect_command_files();

        $this->load_commands($command_files);

        $this->aliases = array();
        $this->categories = array();

        $subclasses = $this->application->classes->subclasses("zesk\Command");
        foreach ($subclasses as $subclass) {
            $this->process_class($subclass);
        }

        ksort($this->categories);
    }

    public function save_aliases(array $aliases) {
        $paths = $this->application->configure_include_path();
        $name = "command-aliases.json"; // Put this in a single location
        $content = JSON::encode_pretty($aliases);
        $conf_file = File::find_first($paths, $name);
        if ($conf_file) {
            if (file_get_contents($conf_file) === $content) {
                return false;
            }

            try {
                File::put($conf_file, $content);
                $this->application->logger->notice("Wrote {file}", array(
                    "file" => $conf_file,
                ));
                return true;
            } catch (\Exception $e) {
                echo get_class($e) . " " . $e->getMessage();
            }
        }
        while (count($paths) > 0) {
            $path = array_pop($paths);

            try {
                $conf_file = path($path, $name);
                File::put($conf_file, $content);
                $this->application->logger->notice("Wrote {file}", array(
                    "file" => $conf_file,
                ));
                return true;
            } catch (\Exception $e) {
                echo get_class($e) . " " . $e->getMessage();
            }
        }
        return false;
    }
}
