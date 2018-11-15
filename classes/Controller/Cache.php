<?php
/**
 * @package zesk
 * @subpackage Controller
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Controller_Cache extends Controller {
    /**
     * Convert a request for file stored elsewhere and store it where the web server will then serve it thereafter.
     *
     * Useful for populating a file system from alternate sources such as share paths, or from the database or a remote store, for example.
     *
     * @param string $contents
     * @return NULL|\zesk\Response Returns NULL if a problem occurred, or zesk\Response to serve contents as file.
     */
    protected function request_to_file($contents) {
        $file = $this->request->path();
        if (!File::path_check($file)) {
            $message = "User accessed {file} which contains suspicious path components while trying to write {contents_size} bytes.";
            $args = array(
                "file" => $file,
                "contents_size" => strlen($contents),
            );
            $this->application->logger->error($message, $args);
            $this->application->hooks->call("security", $message, $args);
            return null;
        }
        $docroot = $this->application->document_root();
        $cache_file = Directory::undot(path($docroot, $file));
        if (!begins($cache_file, $docroot)) {
            $this->application->hooks->call("security", "User cache file \"{cache_file}\" does not match document root \"{docroot}\"", array(
                "cache_file" => $cache_file,
                "docroot" => $docroot,
            ));
            return null;
        }
        if ($this->request->get('nocache') === $this->option("nocache_key", microtime(true))) {
            return $this->response->content_type(MIME::from_filename($cache_file))->header('Content-Length', strlen($contents))->content($contents);
        }
        Directory::depend(dirname($cache_file), $this->option("cache_directory_mode", 0775));
        file_put_contents($cache_file, $contents);
        return $this->response->file($cache_file);
    }
}
