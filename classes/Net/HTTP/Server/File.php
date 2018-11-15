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
class Net_HTTP_Server_File extends Net_HTTP_Server {
    protected $default_driver = self::type_single;

    protected $root_path = null;

    protected $directory_list = false;

    final public function root_path($set = null) {
        if ($set !== null) {
            if (!is_dir($set)) {
                throw new Exception_Directory_NotFound($set);
            }
            $this->root_path = $set;
            return $this;
        }
        return $this->root_path;
    }

    final public function directory_list($set = null) {
        if ($set !== null) {
            $this->directory_list = to_bool($set);
            return $this;
        }
        return $this->directory_list;
    }

    final protected function handle_request(Net_HTTP_Server_Request $request, Net_HTTP_Server_Response $response) {
        $uri = $request->uri;
        
        if ($this->root_path === null) {
            throw new Net_HTTP_Server_Exception(Net_HTTP::STATUS_FILE_NOT_FOUND, null, "root_path is not set");
        }
        $real_root_path = realpath($this->root_path);
        $full_path = realpath(path($real_root_path, $uri));
        if (!begins($full_path, $real_root_path)) {
            throw new Net_HTTP_Server_Exception(Net_HTTP::STATUS_UNAUTHORIZED, null, "Request outside of root directory");
        }
        if (is_dir($full_path)) {
            $index_file = path($full_path, "index.html");
            if (is_file($index_file)) {
                $full_path = $index_file;
            } elseif ($this->directory_list) {
                $files = Directory::ls($full_path);
                $result = array();
                foreach ($files as $f) {
                    $d = is_dir(path($full_path, $f));
                    if ($d) {
                        $f = "$f/";
                    }
                    $result[] = HTML::tag("a", array(
                        "href" => $f,
                    ), $d ? HTML::tag("strong", $f) : $f);
                }
                $response->content = HTML::tag("ul", HTML::tags("li", $result));
                $response->content_type = "text/html";
                return;
            }
        }
        
        if (!is_file($full_path)) {
            throw new Net_HTTP_Server_Exception(Net_HTTP::STATUS_FILE_NOT_FOUND, null, "$uri not found");
        }
        $response->filename($full_path);
    }
}
