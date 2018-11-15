<?php

/**
 *
 */
namespace zesk;

/**
 * Main share controller
 *
 * @author kent
 * @see docs/share.md
 */
class Controller_Share extends Controller {
    /**
     *
     * @param unknown $path
     * @return string
     */
    public function path_to_file($path) {
        $uri = StringTools::unprefix($path, "/");
        list($ignore, $uri) = pair($uri, "/", null, $uri);
        $share_paths = $this->application->share_path();
        foreach ($share_paths as $name => $path) {
            if (empty($name) || begins($uri, "$name/")) {
                $file = path($path, StringTools::unprefix($uri, "$name/"));
                if (!is_dir($file) && file_exists($file)) {
                    return $file;
                }
            }
        }
        return null;
    }
    
    /**
     *
     * {@inheritdoc}
     *
     * @see Controller::_action_default()
     */
    public function _action_default($action = null) {
        $uri = StringTools::unprefix($original_uri = $this->request->path(), "/");
        if ($this->application->development() && $uri === "share/debug") {
            $this->response->content = self::share_debug();
            return;
        }
        $file = $this->path_to_file($this->request->path());
        if (!$file) {
            $this->error_404();
            return;
        }
        $mod = $this->request->header('If-Modified-Since');
        $fmod = filemtime($file);
        if ($mod && $fmod <= strtotime($mod)) {
            $this->response->status(Net_HTTP::STATUS_NOT_MODIFIED);
            $this->response->content_type(MIME::from_filename($file));
            $this->response->content = "";
            if ($this->option_bool('build')) {
                $this->build($original_uri, $file);
            }
            return;
        }
        
        $this->response->header("X-Debug", "Mod - " . strtotime($mod) . " FMod - " . $fmod);
        $request = $this->request;
        if ($request->get("_ver")) {
            // Versioned resources are timestamped, expire never
            $this->response->header_date("Expires", strtotime("+1 year"));
        } else {
            $this->response->header_date("Expires", strtotime("+1 hour"));
        }
        $this->response->raw()->file($file);
        if ($this->option_bool('build')) {
            $this->build($original_uri, $file);
        }
    }
    
    /**
     * Copy file to destination so web server serves it directly next time
     *
     * @param string $path
     * @param string $file
     */
    private function build($path, $file) {
        $target = path($this->application->document_root(), $path);
        Directory::depend(dirname($target), 0775);
        $status = copy($file, $target);
        $this->application->logger->notice("Copied {file} to {target} - {status}", array(
            "file" => $file,
            "target" => $target,
            "status" => $status ? "true" : "false",
        ));
    }
    
    /**
     * Output debug information during development
     */
    private function share_debug() {
        $content = "";
        $content .= HTML::tag("h1", "Server") . HTML::tag("pre", PHP::dump($_SERVER));
        $content .= HTML::tag("h1", "Request headers") . HTML::tag('pre', PHP::dump($this->request->header()));
        $content .= HTML::tag("h1", "Shares") . HTML::tag('pre', PHP::dump($this->application->share_path()));
        return $content;
    }
    
    /**
     *
     * @param string $path
     * @return string
     */
    public static function realpath(Application $application, $path) {
        $path = explode("/", trim($path, '/'));
        array_shift($path);
        $share = array_shift($path);
        $shares = $application->share_path();
        if (array_key_exists($share, $shares)) {
            return path($shares[$share], implode("/", $path));
        }
        return null;
    }
    
    /**
     * Clear the share build path upon cache clear
     */
    public function hook_cache_clear() {
        $logger = $this->application->logger;
        /* @var $locale \zesk\Locale */
        $logger->debug(__METHOD__);
        if ($this->option_bool('build')) {
            $share_dir = path($this->application->document_root(), 'share');
            if (is_dir($share_dir)) {
                $logger->notice('{class}::hook_cache_clear - deleting {share_dir}', array(
                    'class' => __CLASS__,
                    'share_dir' => $share_dir,
                ));
                Directory::delete($share_dir);
            } else {
                $logger->notice('{class}::hook_cache_clear - would delete {share_dir} but it is not found', array(
                    'class' => __CLASS__,
                    'share_dir' => $share_dir,
                ));
            }
        }
    }
}
