<?php

/**
 * 
 */
namespace zesk;

/**
 * Should probably do this in Apache or nginx, not in code
 *
 * @author kent
 */
class Module_GZIP extends Module {
	/**
	 * Whether output buffering is enabled
	 * 
	 * @var boolean
	 */
	static $started = false;
	
	/**
	 * Gzip enabled and running
	 * 
	 * @var boolean
	 */
	static $ok = null;
	
	/**
	 * Hook Module::hooks()
	 */
	public static function hooks(Kernel $zesk) {
		$hooks = $zesk->hooks;
		$hooks->add("zesk\Response::headers", __CLASS__ . "::response_headers");
		$hooks->add("zesk\Response::render", __CLASS__ . "::response_render");
		$hooks->add("zesk\Response::rendered", __CLASS__ . "::response_rendered");
		$hooks->add("zesk\Response::output", __CLASS__ . "::response_output");
		$hooks->add("zesk\Response::outputted", __CLASS__ . "::response_outputted");
	}
	
	/**
	 * Hook Response::header
	 * 
	 * @param Response $response        	
	 */
	public static function response_headers(Response $response) {
		if (self::ok($response->request)) {
			$response->header("Content-Encoding", "gzip");
		}
	}
	
	/**
	 * Hook Response::output
	 * 
	 * @param Response $response        	
	 */
	public static function response_output(Response $response) {
		if (self::ok($response->request)) {
			ob_start();
			self::$started = true;
		}
	}
	/**
	 * Hook Response::outputted
	 * 
	 * @param Response $response        	
	 */
	public static function response_outputted(Response $response) {
		if (self::$started) {
			echo self::handler(ob_get_clean());
		}
	}
	
	/**
	 * Hook Response::render
	 * 
	 * @param Response $response        	
	 */
	public static function response_render(Response $response) {
		if (self::ok($response->request)) {
			self::$started = true;
		}
	}
	/**
	 * Hook Response::rendered
	 * 
	 * @param Response $response        	
	 */
	public static function response_rendered(Response $response, $content) {
		return self::$started ? self::handler($content) : $content;
	}
	
	/**
	 * Is GZIP enabled?
	 * 
	 * @param boolean $set        	
	 * @return boolean
	 */
	public static function enabled($set = null) {
		global $zesk;
		/* @var $zesk Kernel */
		if (is_bool($set)) {
			$zesk->configuration->path(__CLASS__)->enabled = to_bool($set);
		}
		return $zesk->configuration->path_get("Module_gzip::enabled", true);
	}
	
	/**
	 * Does the request support GZIP output?
	 * Note: caches result
	 * 
	 * @param Request $request        	
	 *
	 * @return boolean
	 */
	public static function ok(Request $request) {
		if (!self::enabled()) {
			return false;
		}
		if (!function_exists('gzencode')) {
			return false;
		}
		if (is_bool(self::$ok)) {
			return self::$ok;
		}
		$accept_encoding = $request->header("Accept-Encoding");
		$this->application->logger->debug("Accept-Encoding: {value}", array(
			"value" => $accept_encoding
		));
		$accept_encoding = explode(",", str_replace(" ", "", $accept_encoding));
		self::$ok = in_array("gzip", $accept_encoding) || in_array("deflate", $accept_encoding);
		return self::$ok;
	}
	
	/**
	 * The GZIP handler
	 * 
	 * @param unknown_type $content        	
	 * @return unknown string
	 */
	private static function handler($content) {
		return gzencode($content, zesk()->configuration->path_get('Module_gzip::compress_level', 9));
	}
}
