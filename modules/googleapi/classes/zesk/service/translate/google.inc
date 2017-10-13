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
class Service_Translate_Google extends Service_Translate {
	/**
	 * 
	 * @var string
	 */
	const api_root = "https://www.googleapis.com/language/translate/v2";
	
	/**
	 * 
	 * @var string
	 */
	const language_codes_url = 'https://developers.google.com/adwords/api/docs/appendix/languagecodes.csv';
	
	/**
	 * 
	 * @var string
	 */
	private $key = null;
	
	/**
	 *
	 * @var Net_HTTP_Client $client
	 */
	public $client = null;
	
	/**
	 *
	 * @var string
	 */
	public $source_language = null;
	
	/**
	 *
	 * @var string
	 */
	public $target_language = null;
	
	/**
	 *
	 * @var array|Service_Translate_Google
	 */
	private $instances = array();
	
	/**
	 * Create a new Service_Translate
	 *
	 * @param string $source_language
	 * @param string $target_language
	 */
	public function __construct(Application $application, $target_language = null, $source_language = null) {
		parent::__construct($application);
		$this->inherit_global_options();
		$this->source_language = $this->option("source", $source_language);
		$this->target_language = $target_language ? $target_language : $this->option("target", Locale::language());
		$this->client = new Net_HTTP_Client($this->application, self::api_root);
		$this->key = $this->option('api_key');
		if (!$this->key) {
			throw new Exception_Configuration("Service_Translate::api_key", "Translation authorization key is not available, unable to automatically translate text.");
		}
	}
	
	/**
	 * Generate the service object
	 *
	 * @param string $source_language
	 * @param string $target_language
	 * @return zesk\Service_Translate_Google
	 */
	static function instance($target_language, $source_language = null) {
		$source = empty($source_language) ? null : Locale::language($source_language);
		if (empty($target_language)) {
			throw new Exception_Semantics("Target language is required");
		}
		$target = Locale::language($target_language);
		$key = "$source-$target";
		if (array_key_exists($key, self::$instances)) {
			return self::$instances[$key];
		}
		return self::$instances[$key] = new Service_Translate_Google($source, $target);
	}
	public function target_language($set = null) {
		if ($set === null) {
			return $this->target_language;
		}
		$this->target_language = $set;
		return $this;
	}
	public function source_language($set = null) {
		if ($set === null) {
			return $this->source_language;
		}
		$this->source_language = $set;
		return $this;
	}
	public function list_languages() {
		$url = URL::query_format(self::api_root . '/languages', array(
			"key" => $this->key
		));
		$languages = $this->_execute($url, 'data.languages');
		return arr::collapse($languages, "language");
	}
	public function translate($phrases) {
		$query_string = $this->_query_string();
		if (!is_array($phrases)) {
			$phrases = array(
				$phrases
			);
		} else {
			$phrases = array_values($phrases);
		}
		$suffix = "";
		foreach ($phrases as $phrase) {
			$suffix .= "&q=" . urlencode($phrase);
		}
		$url = URL::query_format(self::api_root, $query_string) . $suffix;
		$indexed_translations = $this->_execute($url, "data.translations");
		if (!is_array($indexed_translations)) {
			throw new Exception_Semantics("Result data.translations from Google is not an array: {content}", compact("content"));
		}
		$translations = array();
		foreach ($phrases as $index => $phrase) {
			$translations[$phrase] = $indexed_translations[$index]['translatedText'];
		}
		return $translations;
	}
	private function _execute($url, $json_path) {
		$client = $this->client;
		$client->url($url);
		$content = $client->go();
		if (!$client->response_code_type() === 2) {
			throw new \Exception("Service response from Google failed: {url} {error} {message}: {content}", array(
				"url" => $url,
				"error" => $client->response_code(),
				"message" => $client->response_message(),
				"content" => $content
			));
		}
		$results = JSON::decode($content);
		$result = apath($results, $json_path, null);
		if ($result === null) {
			throw new Exception_Semantics("Result from Google does not contain path {json_path}: {content}", compact("content", "json_path"));
		}
		return $result;
	}
	private function _query_string() {
		$result = array(
			"key" => $this->key,
			"target" => $this->target_language
		);
		if ($this->source_language) {
			$result["source"] = $this->source_language;
		}
		return $result;
	}
}
