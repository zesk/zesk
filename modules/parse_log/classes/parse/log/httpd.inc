<?php
namespace zesk;

class Parse_Log_HTTPd extends Parse_Log {
	
	/**
	 * Known patterns
	 *
	 * @return array
	 */
	protected function patterns() {
		return parent::patterns() + array(
			'http-date' => array(
				'pattern' => '\[[^\]]+\]',
				'function' => 'Parse_Log::strtotime',
				'synonyms' => 'timestamp'
			),
			'http-request' => array(
				'pattern' => '"([A-Z]+) ([^ ]+) HTTP/([0-9.]+)"',
				'captures' => array(
					'http-request-method',
					'http-request-uri',
					'http-request-version'
				)
			),
			'http-status' => self::pattern_number,
			'http-referrer' => array(
				'pattern' => self::pattern_dquoted_string,
				'function' => __CLASS__ . '::unquote'
			),
			'http-user-agent' => array(
				'pattern' => self::pattern_dquoted_string,
				'function' => __CLASS__ . '::unquote'
			)
		);
	}
}

// example.org 76.120.67.80 - - [21/Oct/2013:00:00:45 +0000] "GET /help HTTP/1.1" 200 914 "http://example.org/help" "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_8_4) AppleWeb
