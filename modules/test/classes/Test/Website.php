<?php
/**
 * @package ruler
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2011, Market Ruler, LLC
 *            This subclass is for basic website testing using Selenium. Should probably expand it to allow for features
 *            specific to certain types of sites.
 *            (Signup, Login, Forgot, etc.)
 *            Needs to be cleaned up as Test_Unit added functionality which is present in here (test iteration, for
 *            example)
 */
namespace zesk;

abstract class Test_Website extends Test_Selenium {
    /**
     * Prefix for urls (for /admin)
     * @var string
     */
    private $url_prefix;

    /**
     *
     * @param unknown $options
     */
    public function __construct($options = null) {
        $options['browser'] = "*chrome";
        $options['test_host'] = "192.168.0.113";
        $options['test_port'] = 14444;
        parent::__construct($options);

        $parts = URL::parse($this->browserUrl);

        $this->url_prefix = rtrim(avalue($parts, 'path', ''), "/");
    }

    abstract public function tests();

    /**
     * Options in environemnt which are honored:
     * single_test Test to run without the "test_" prefix
     * begin_test Test to start with in the lineup
     * speed Number of milliseconds to wait between test steps
     * run_default Boolean value to run all tests, by default
     * test-name Run this test when run_default is false
     * sleep_before_stop Sleep this many seconds before quitting the app. Only for caught exceptions, not PHP errors.
     * @return unknown
     */
    public function test() {
        echo "Testing site: " . $this->browserUrl . "\n";

        $this->start();

        $single_test = $this->option("single_test");
        $begin_test = $this->option("begin_test");
        $run_default = $this->option_bool("run_default", true);
        $tests = array();
        if ($single_test) {
            if (!in_array($single_test, $this->tests())) {
                $this->failed("No such test $single_test");
            }
            echo "####### Single test: $single_test\n";
            $tests = array(
                $single_test,
            );
        } elseif ($begin_test) {
            $all_tests = $this->tests();
            if (!in_array($begin_test, $all_tests)) {
                $this->failed("No such test $begin_test");
            }
            $tests = array();
            $found = false;
            foreach ($all_tests as $test) {
                if ($test === $begin_test || $found) {
                    $tests[] = $test;
                    $found = true;
                }
            }
            echo "####### Beginning from test: $begin_test\n";
        } else {
            $tests = $this->tests();
        }
        $exception = null;
        $speed = $this->option_integer("speed");
        if ($speed) {
            $this->setSpeed($speed);
            echo "Setting speed to $speed microseconds between steps.\n";
        }
        $method = null;

        try {
            foreach ($tests as $test) {
                if ($this->option_bool($test, $run_default)) {
                    $method = "test_$test";
                    $this->push($test);
                    echo "### BEGIN $test...\n";
                    $this->$method();
                    echo "### END $test...\n";
                    $this->pop();
                }
            }
        } catch (Exception $e) {
            $exception = $e;
        }
        $sleep_time = $this->option_integer("sleep_before_stop", 0);
        if ($exception && $sleep_time > 0) {
            $this->application->logger->error("Error occurred: {message}", array(
                "message" => $exception->getMessage(),
                "exception" => $exception,
            ));
            $this->message("Sleeping before stopping ...");
            sleep($sleep_time);
        }
        $this->stop();
        if ($exception) {
            throw $exception;
        }

        return self::TEST_RESULT_OK;
    }

    public function open_url($url) {
        throw new Exception_Unimplemented("TODO");
        return parent::open($this->url_prefix . $url);
    }

    public function waitForPageToLoad($timeout = "30000", $reverse_test = false) {
        parent::waitForPageToLoad($timeout);

        if ($reverse_test) {
            $this->assertHTMLContains('PHP-ERROR');
        } else {
            $this->assertHTMLDoesNotContain('PHP-ERROR');
        }
    }

    public function shouldSkipPage($content, $path) {
        $skip_page_tag = $this->option('skip_page_tag');
        if ($skip_page_tag && strpos($content, $skip_page_tag) !== false) {
            $this->message("Skipping $path ... INTERNAL");
            return true;
        }
        return false;
    }

    public function validate_method_default() {
        $content = $this->getBodyText();
        $visited_link = $this->getLocation();
        if (strpos($content, "PHP-ERROR") !== false) {
            $error = "#### ERROR: PHP Error or Warning found: $visited_link";
            echo "$error\n";
            echo "CONTENT:\n";
            echo $content;
            echo "\nEND CONTENT;\n";
            return $error;
        }
        return true;
    }

    protected function click_all_linkable_pages($start_page = "/", $skip_links = null, $validate_method = "validate_method_default", $logprefix = "#") {
        $this->application->logger->error("$logprefix _click_all_linkable_pages START");
        if (!is_array($skip_links)) {
            $skip_links = array();
        }
        $this->open($start_page);
        $this->waitForPageToLoad();
        $errors = array();
        $handled_links = array();
        $external_links = array();
        $download_links = array();
        $mailto_links = array();
        $links = array();
        do {
            $visited_link = $this->getLocation();

            if ($validate_method) {
                $error = $this->$validate_method();
                if ($error !== true) {
                    $errors[] = $error;
                }
            }

            $visited_link_norm = URL::path($visited_link);
            $handled_links[$visited_link_norm] = true;
            // if (!array_key_exists($visited_link_norm, $files)) {
            // echo "$logprefix FILES key doesn't exist $visited_link_norm\n";
            // }
            $html_source = $this->getHtmlSource();
            $aa = HTML::extract_tags("a", $html_source, false);
            if (is_array($aa)) {
                foreach ($aa as $tag) {
                    $href = $tag->option("href");
                    if ($href === null) {
                        continue;
                    }
                    if (begins($href, "javascript:")) {
                        // echo "$logprefix skipping $href\n";
                    } elseif (begins($href, "http")) {
                        $href = StringTools::left($href, "?");
                        $external_links[$href] = $href;
                    // echo "$logprefix skipping external link $href\n";
                    } elseif (ends($href, ".zip")) {
                        $download_links[$href] = $href;
                    // echo "$logprefix skipping download link $href\n";
                    } else {
                        $href_norm = StringTools::left(StringTools::left($href, "?"), "#");
                        if (empty($href_norm)) {
                            // echo "$logprefix BLANK LINK: $href\n";
                            continue;
                        }
                        if ($href_norm[0] !== '/') {
                            if (begins($href_norm, "mailto:")) {
                                $mailto_links[$href_norm] = $href_norm;

                                continue;
                            }
                            $cur_path = StringTools::ends($visited_link_norm, "/") ? $visited_link_norm : dirname($visited_link_norm);
                            // echo "$logprefix visited_link_norm is $cur_path\n";
                            // echo "$logprefix $href_norm becomes dirname(" . $cur_path . ") => ";
                            $href_norm = path($cur_path, $href_norm);
                            $href = path($cur_path, $href);
                            // echo "$href_norm\n";
                        }
                        $href_norm = Directory::undot($href_norm);
                        $href = Directory::undot($href);
                        if (in_array($href_norm, $skip_links)) {
                            // echo "$logprefix IGNORE LINK: $href_norm\n";
                            continue;
                        }
                        // echo "$href => $href_norm\n";
                        if (avalue($handled_links, $href_norm)) {
                            // echo "$logprefix ALREADY VISITED $href\n";
                        } else {
                            // echo "$logprefix ADDING $href\n";
                            $links[] = array(
                                $href,
                                $visited_link,
                            );
                            $handled_links[$href_norm] = true;
                        }
                    }
                }
            }
            // $link = $referrer = null;
            list($link, $referrer) = array_shift($links);
            if ($link) {
                echo "$logprefix Next page is: $link\n";
                echo "$logprefix Referrer is: $referrer\n";
                $this->open($link);
            }
        } while (count($links) > 0);
        $locale = $this->application->locale;
        if (count($external_links) > 0) {
            echo $locale->plural_word("External Link", count($external_links)) . ":\n\t" . implode("\n\t", $external_links) . "\n";
        }
        if (count($download_links) > 0) {
            echo $locale->plural_word("Download Link", count($download_links)) . ":\n\t" . implode("\n\t", $download_links) . "\n";
        }
        if (count($mailto_links) > 0) {
            echo $locale->plural_word("Mail Link", count($mailto_links)) . ":\n\t" . implode("\n\t", $mailto_links) . "\n";
        }
        if (count($errors) > 0) {
            echo $locale->plural_word("Error", count($errors)) . ":\n\t" . implode("\n\t", $errors) . "\n";
            $this->assert(false);
        }
        $this->application->logger->error("$logprefix _click_all_linkable_pages DONE");
    }

    /**
     *
     * @deprecated
     *
     */
    protected function walkAllPages($directory_root, $skip_links = null, $exclude_files = null) {
        return $this->walk_all_pages($directory_root, $skip_links, $exclude_files);
    }

    protected function walk_all_pages($directory_root, $skip_links = null, $exclude_files = null) {
        $validate_method = $this->option('validate_method', 'validate_method_default');

        $options['file_include_pattern'] = '/\.php$/';
        $options['file_exclude_pattern'] = false;
        $options['directory_walk_exclude_pattern'] = '/\.svn/';
        $options['directory_include_pattern'] = false;
        $options['directory_exclude_pattern'] = '/\.svn/';
        echo "Files path is $directory_root ...\n";
        $files = Directory::list_recursive($directory_root, $options);

        $links = array();

        $files = ArrayTools::prefix($files, "/");
        $files = array_flip($files);
        $files_names = array_keys($files);
        foreach ($files_names as $k) {
            if (StringTools::ends($k, "/index.php")) {
                $k = substr($k, 0, -strlen("index.php"));
            }
            $files[$k] = false;
        }
        $handled_links = array();
        $download_links = array();
        $external_links = array();
        $logprefix = "######";
        $errors = array();
        do {
            $visited_link = $this->getLocation();

            $error = $this->$validate_method();
            if ($error !== true) {
                $errors[] = $error;
            }

            $visited_link_norm = URL::path($visited_link);
            $handled_links[$visited_link_norm] = true;
            if (!array_key_exists($visited_link_norm, $files)) {
                echo "$logprefix FILES key doesn't exist $visited_link_norm\n";
            } else {
                $files[$visited_link_norm] = true;
            }
            $html_source = $this->getHtmlSource();
            $aa = HTML::extract_tags("a", $html_source, false);
            if (is_array($aa)) {
                foreach ($aa as $tag) {
                    $href = $tag->option("href");
                    if ($href === null) {
                        continue;
                    }
                    if (begins($href, "javascript:")) {
                        echo "$logprefix skipping $href\n";
                    } elseif (begins($href, "http")) {
                        $external_links[$href] = $href;
                        echo "$logprefix skipping external link $href\n";
                    } elseif (ends($href, ".zip")) {
                        $download_links[$href] = $href;
                        echo "$logprefix skipping download link $href\n";
                    } else {
                        $href_norm = StringTools::left(StringTools::left($href, "?"), "#");
                        if (empty($href_norm)) {
                            // echo "$logprefix BLANK LINK: $href\n";
                            continue;
                        }
                        if ($href_norm[0] !== '/') {
                            if (begins($href_norm, "mailto:")) {
                                echo "$logprefix MAILTO link: $href_norm\n";

                                continue;
                            }
                            $cur_path = StringTools::ends($visited_link_norm, "/") ? $visited_link_norm : dirname($visited_link_norm);
                            // echo "$logprefix visited_link_norm is $cur_path\n";
                            // echo "$logprefix $href_norm becomes dirname(" . $cur_path . ") => ";
                            $href_norm = path($cur_path, $href_norm);
                            $href = path($cur_path, $href);
                            // echo "$href_norm\n";
                        }
                        $href_norm = Directory::undot($href_norm);
                        $href = Directory::undot($href);
                        if (in_array($href_norm, $skip_links)) {
                            echo "$logprefix IGNORE LINK: $href_norm\n";

                            continue;
                        }
                        // echo "$href => $href_norm\n";
                        if (avalue($handled_links, $href_norm)) {
                            // echo "$logprefix ALREADY VISITED $href\n";
                        } else {
                            echo "$logprefix ADDING $href\n";
                            $links[] = array(
                                $href,
                                $visited_link,
                            );
                            $handled_links[$href_norm] = true;
                        }
                    }
                }
            }
            $link = $referrer = null;
            list($link, $referrer) = array_shift($links);
            if ($link) {
                echo "$logprefix Next page is: $link\n";
                echo "$logprefix Referrer is: $referrer\n";
                $this->open($link);
            }
        } while (count($links) > 0);

        if (!is_array($exclude_files)) {
            $this->message("\$exclude_files is not an array");
            $exclude_files = array();
        }
        foreach ($files as $f => $visited) {
            if (ArrayTools::find($f, $exclude_files)) {
                continue;
            }
            $test_path = path($directory_root, $f);
            $contents = file_get_contents($test_path);
            if ($this->shouldSkipPage($contents, $test_path)) {
                continue;
            }
            if (!$visited) {
                echo "$logprefix No links to $f ???\n";
            } else {
                echo "$logprefix Visited $f, skipping\n";

                continue;
            }
            echo "$logprefix OPEN: $f\n";
            $this->open("$f");
            $error = $this->$validate_method();
            if ($error !== true) {
                $errors[] = $error;
            }
        }

        echo "\n";
        echo "#################\n";
        echo "### COMPLETED ###\n";
        echo "#################\n";
        echo "\n";

        $this->assert(count($errors) === 0, implode("\n", $errors));
    }
}
