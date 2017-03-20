<?php
namespace zesk;

class Feed extends Model implements \Iterator {
	
	/**
	 * 
	 * @var string
	 */
	protected $url = null;
	
	/**
	 * 
	 * @var array
	 */
	protected $posts = array();
	
	/**
	 * 
	 * @var array
	 */
	protected $errors = array();
	
	/**
	 * 
	 * @param unknown $file_or_url
	 */
	function __construct($url, array $options = array()) {
		parent::__construct(null, $options);
		$this->url($url);
	}
	
	/**
	 * Getter/setter for URL
	 * @param unknown $set
	 * @throws \Exception_Syntax
	 */
	function url($set = null) {
		if ($set !== null) {
			if (!URL::valid($set)) {
				throw new Exception_Syntax("Invalid URL {url}", array(
					"url" => $set
				));
			}
			$this->url = $set;
			return $this;
		}
		return $this->url;
	}
	function errors() {
		return $this->errors;
	}
	private static function process_error(\LibXMLError $error) {
		// 		$return = $xml[$error->line - 1] . "\n";
		// 		$return .= str_repeat('-', $error->column) . "^\n";
		switch ($error->level) {
			case LIBXML_ERR_WARNING:
				$return .= "Warning $error->code: ";
				break;
			case LIBXML_ERR_ERROR:
				$return .= "Error $error->code: ";
				break;
			case LIBXML_ERR_FATAL:
				$return .= "Fatal Error $error->code: ";
				break;
		}
		
		$return .= trim($error->message) . "\n  Line: $error->line" . "\n  Column: $error->column";
		if ($error->file) {
			$return .= "\n  File: $error->file";
		}
		return "$return\n\n--------------------------------------------\n\n";
	}
	
	/**
	 * Convert errors into strings
	 * 
	 * @param array $errors
	 */
	private static function process_errors(array $errors) {
		foreach ($errors as $index => $error) {
			$errors[$index] = self::process_error($error);
		}
		return $errors;
	}
	function load_remote_url() {
		$http = new Net_HTTP_Client($this->url);
		try {
			$content = $http->go();
			return $content;
		} catch (Net_HTTP_Client_Exception $e) {
			return $e;
		}
	}
	/**
	 * 
	 * @return NULL|\zesk\Feed
	 */
	function execute() {
		$this->errors = array();
		
		$content = $this->load_remote_url();
		if ($content instanceof Exception) {
			$this->errors['http'] = $content->getMessage();
			return null;
		}
		if (!($x = @simplexml_load_string($content))) {
			$this->errors = $this->process_errors(libxml_get_errors());
			libxml_clear_errors();
			return null;
		}
		foreach ($x->channel->item as $item) {
			$post = new Feed_Post();
			$post->raw_date = (string) $item->pubDate;
			$post->date = new Timestamp($item->pubDate);
			$post->link = (string) $item->link;
			$post->title = (string) $item->title;
			$post->description = (string) $item->description;
			
			$this->posts[] = $post;
		}
		return $this;
	}
	public function posts() {
		return $this->posts;
	}
	/**
	 * 
	 */
	public function current() {
		return current($this->posts);
	}
	
	/**
	 * 
	 */
	public function next() {
		next($this->posts);
	}
	
	/**
	 * 
	 */
	public function key() {
		return key($this->posts);
	}
	
	/**
	 * 
	 */
	public function valid() {
		return $this->key() !== null;
	}
	
	/**
	 * 
	 */
	public function rewind() {
		reset($this->posts);
	}
}
